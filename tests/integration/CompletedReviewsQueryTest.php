<?php

use APP\core\Application;
use APP\core\PageRouter;
use APP\facades\Repo;
use APP\plugins\generic\reviewersControlReport\classes\RCRClosedDateInterval;
use APP\plugins\generic\reviewersControlReport\classes\ReviewersControlReportDAO;
use Illuminate\Support\Facades\DB;
use PKP\db\DAORegistry;
use PKP\facades\Locale;
use PKP\security\Role;
use PKP\submission\PKPSubmission;
use PKP\submission\reviewAssignment\ReviewAssignment;

require_once __DIR__ . '/../ReviewersControlReportTestCase.php';

class CompletedReviewsQueryTest extends ReviewersControlReportTestCase
{
    private $dao;
    private $locale = 'en';
    private $contextId;
    private $otherContextId;
    private $reviewerId;
    private $submissionOfContext;
    private $submissionOfOtherContext;

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
        $this->dao = new ReviewersControlReportDAO();
        $this->contextId = $this->fixture->createContext('rcr-primary');
        $this->otherContextId = $this->fixture->createContext('rcr-secondary');
        $this->reviewerId = $this->fixture->createUser();
        $this->submissionOfContext = $this->fixture->createSubmission($this->contextId, 'Central do Brasil');
        $this->submissionOfOtherContext = $this->fixture->createSubmission($this->otherContextId, 'Cidade de Deus');
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function createReviewAssignment($submissionId, $dateCompleted, $overrides = []): void
    {
        $this->fixture->createCompletedReview(
            $submissionId,
            $overrides['reviewerId'] ?? $this->reviewerId,
            $dateCompleted,
            $overrides
        );
    }

    public function testReturnsCompletedReviewsOfTheContextWhenNoIntervalIsGiven()
    {
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 14:32:00');
        $this->createReviewAssignment($this->submissionOfContext, '2026-03-20 09:00:00');

        $completedReviews = $this->dao->getCompletedReviews($this->contextId, null, $this->reviewerId);

        $this->assertCount(2, $completedReviews);
    }

    public function testDoesNotReturnReviewsOfAnotherContext()
    {
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 14:32:00');
        $this->createReviewAssignment($this->submissionOfOtherContext, '2026-01-16 14:32:00');

        $primaryReviews = $this->dao->getCompletedReviews($this->contextId, null, $this->reviewerId);
        $secondaryReviews = $this->dao->getCompletedReviews($this->otherContextId, null, $this->reviewerId);

        $this->assertCount(1, $primaryReviews);
        $this->assertSame($this->submissionOfContext, $primaryReviews[0]->getSubmissionId());
        $this->assertCount(1, $secondaryReviews);
        $this->assertSame($this->submissionOfOtherContext, $secondaryReviews[0]->getSubmissionId());
    }

    public function testDoesNotReturnUnfinishedDeclinedOrCancelledReviews()
    {
        $this->createReviewAssignment($this->submissionOfContext, null);
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 14:32:00', ['declined' => 1]);
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-16 14:32:00', ['cancelled' => 1]);

        $completedReviews = $this->dao->getCompletedReviews($this->contextId, null, $this->reviewerId);

        $this->assertEquals([], $completedReviews);
    }

    public function testReturnsOnlyReviewsCompletedInsideTheGivenInterval()
    {
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 14:32:00');
        $this->createReviewAssignment($this->submissionOfContext, '2026-03-20 09:00:00');

        $interval = new RCRClosedDateInterval('2026-03-01', '2026-03-31');
        $completedReviews = $this->dao->getCompletedReviews($this->contextId, $interval, $this->reviewerId);

        $this->assertCount(1, $completedReviews);
        $this->assertEquals('2026-03-20 09:00:00', $completedReviews[0]->getDateCompleted());
    }

    public function testIntervalIncludesReviewsCompletedAnyTimeOfTheBoundaryDays()
    {
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 00:00:01');
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-17 23:59:58');

        $interval = new RCRClosedDateInterval('2026-01-15', '2026-01-17');
        $completedReviews = $this->dao->getCompletedReviews($this->contextId, $interval, $this->reviewerId);

        $this->assertCount(2, $completedReviews);
    }

    public function testQueriesDoNotGrowWithTheNumberOfCompletedReviews()
    {
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 14:32:00');
        $this->createReviewAssignment($this->submissionOfContext, '2026-02-15 14:32:00');
        $queriesOfTwoReviews = $this->countQueriesOfCompletedReviews();

        $otherSubmission = $this->fixture->createSubmission($this->contextId, 'Ainda Estou Aqui');
        $this->createReviewAssignment($otherSubmission, '2026-03-15 14:32:00');
        $this->createReviewAssignment($otherSubmission, '2026-04-15 14:32:00');
        $queriesOfFourReviews = $this->countQueriesOfCompletedReviews();

        $this->assertSame($queriesOfTwoReviews, $queriesOfFourReviews);
    }

    public function testEachCompletedReviewCarriesTheTitleOfItsOwnSubmission()
    {
        $otherSubmission = $this->fixture->createSubmission($this->contextId, 'Ainda Estou Aqui');
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 14:32:00');
        $this->createReviewAssignment($otherSubmission, '2026-03-15 14:32:00');

        $completedReviews = $this->dao->getCompletedReviews($this->contextId);

        $this->assertCount(2, $completedReviews);
        $this->assertEquals('Central do Brasil', $completedReviews[0]->getSubmissionTitle());
        $this->assertEquals('Ainda Estou Aqui', $completedReviews[1]->getSubmissionTitle());
    }

    private function countQueriesOfCompletedReviews(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->dao->getCompletedReviews($this->contextId);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return count($queries);
    }

    public function testCompletedReviewCarriesTheDataTheReportNeeds()
    {
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 14:32:00');

        $completedReview = $this->dao->getCompletedReviews($this->contextId, null, $this->reviewerId)[0];

        $this->assertEquals($this->reviewerId, $completedReview->getReviewerId());
        $this->assertEquals($this->submissionOfContext, $completedReview->getSubmissionId());
        $this->assertEquals('Central do Brasil', $completedReview->getSubmissionTitle());
        $this->assertEquals(1, $completedReview->getRound());
        $this->assertEquals('2026-01-02 10:00:00', $completedReview->getDateAssigned());
        $this->assertEquals('2026-01-20 00:00:00', $completedReview->getDateDue());
        $this->assertEquals(
            ReviewAssignment::SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
            $completedReview->getRecommendation()
        );
        $this->assertEquals(4, $completedReview->getQuality());
    }

    public function testGridAffiliationFallsBackWhenUiLocaleHasNoValue()
    {
        $this->fixture->giveUserTheRole($this->reviewerId, Role::ROLE_ID_REVIEWER);

        $this->useLocale('pt_BR');

        $reviewers = $this->dao->getReviewers(RCRTestFixture::SEEDED_CONTEXT_ID);

        $this->assertArrayHasKey($this->reviewerId, $reviewers);
        $this->assertSame('Agência Nacional do Cinema', $reviewers[$this->reviewerId]->getAffiliation());
    }
}
