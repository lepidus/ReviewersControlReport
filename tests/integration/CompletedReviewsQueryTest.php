<?php

use APP\facades\Repo;
use APP\plugins\generic\reviewersControlReport\classes\RCRClosedDateInterval;
use APP\plugins\generic\reviewersControlReport\classes\ReviewersControlReportDAO;
use Illuminate\Support\Facades\DB;
use PKP\db\DBResultRange;
use PKP\security\Role;
use PKP\submission\reviewAssignment\ReviewAssignment;

require_once __DIR__ . '/../ReviewersControlReportTestCase.php';
require_once __DIR__ . '/../RCRReportFixtures.php';

class CompletedReviewsQueryTest extends ReviewersControlReportTestCase
{
    use RCRReportFixtures;

    private $dao;
    private $locale = 'en';
    // Context ids of their own, so the reviews seeded in the test database
    // (all under journal 1) do not leak into the assertions
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
        $this->contextId = $this->createContext('rcr' . uniqid());
        $this->otherContextId = $this->createContext('rcr' . uniqid());
        $this->reviewerId = $this->createReviewer();
        $this->submissionOfContext = $this->createSubmission($this->contextId, 'Central do Brasil');
        $this->submissionOfOtherContext = $this->createSubmission($this->otherContextId, 'Cidade de Deus');
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function createReviewer(): int
    {
        $suffix = uniqid();

        return $this->createUser(['userName' => 'rcr' . $suffix, 'email' => 'rcr.' . $suffix . '@example.test']);
    }

    private function createReviewAssignment($submissionId, $dateCompleted, $overrides = []): void
    {
        $this->createCompletedReview(
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

        $completedReviews = $this->dao->getCompletedReviews($this->contextId, null);

        $this->assertCount(2, $completedReviews);
    }

    public function testDisabledReviewersRemainInReportAndPaginatedGrid()
    {
        $group = Repo::userGroup()->newDataObject([
            'contextId' => $this->contextId,
            'roleId' => Role::ROLE_ID_REVIEWER,
            'name' => [$this->locale => 'Reviewers'],
            'abbrev' => [$this->locale => 'R'],
        ]);
        $groupId = Repo::userGroup()->add($group);
        Repo::userGroup()->assignUserToGroup($this->reviewerId, $groupId);
        $reviewer = Repo::user()->get($this->reviewerId);
        Repo::user()->edit($reviewer, ['disabled' => true]);
        $activeReviewerId = $this->createReviewer();
        Repo::userGroup()->assignUserToGroup($activeReviewerId, $groupId);
        Repo::user()->edit(Repo::user()->get($activeReviewerId), [
            'familyName' => [$this->locale => 'Zulu'],
        ]);

        $this->assertContains($this->reviewerId, $this->dao->getReviewersIds($this->contextId));
        $grid = $this->dao->getReviewersPage($this->contextId, new DBResultRange(1, 1));
        $this->assertSame(2, $grid->getCount());
        $this->assertArrayHasKey($this->reviewerId, $grid->toArray());
        $this->assertCount(1, $grid->toArray());
        $secondPage = $this->dao->getReviewersPage($this->contextId, new DBResultRange(1, 2));
        $this->assertSame(2, $secondPage->getPage());
        $this->assertArrayHasKey($activeReviewerId, $secondPage->toArray());
        $this->assertCount(1, $secondPage->toArray());
        $this->assertSame([], $this->dao->getReviewersIds($this->otherContextId));
    }

    public function testFullListCarriesEveryReviewerOfTheContext()
    {
        $otherReviewerId = $this->createReviewer();
        $this->giveUserTheRole($this->reviewerId, Role::ROLE_ID_REVIEWER, $this->contextId);
        $this->giveUserTheRole($otherReviewerId, Role::ROLE_ID_REVIEWER, $this->contextId);

        $reviewers = $this->dao->getReviewers($this->contextId);

        $this->assertEqualsCanonicalizing([$this->reviewerId, $otherReviewerId], array_keys($reviewers));
        $this->assertSame([], $this->dao->getReviewers($this->otherContextId));
    }

    public function testDoesNotReturnReviewsOfAnotherContext()
    {
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 14:32:00');
        $this->createReviewAssignment($this->submissionOfOtherContext, '2026-01-16 14:32:00');

        $completedReviews = $this->dao->getCompletedReviews($this->contextId, null);

        $this->assertCount(1, $completedReviews);
        $this->assertEquals('Central do Brasil', $completedReviews[0]->getSubmissionTitle());
    }

    public function testDoesNotReturnUnfinishedDeclinedOrCancelledReviews()
    {
        $this->createReviewAssignment($this->submissionOfContext, null);
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 14:32:00', ['declined' => 1]);
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-16 14:32:00', ['cancelled' => 1]);

        $completedReviews = $this->dao->getCompletedReviews($this->contextId, null);

        $this->assertEquals([], $completedReviews);
    }

    public function testReturnsOnlyReviewsCompletedInsideTheGivenInterval()
    {
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 14:32:00');
        $this->createReviewAssignment($this->submissionOfContext, '2026-03-20 09:00:00');

        $interval = new RCRClosedDateInterval('2026-03-01', '2026-03-31');
        $completedReviews = $this->dao->getCompletedReviews($this->contextId, $interval);

        $this->assertCount(1, $completedReviews);
        $this->assertEquals('2026-03-20 09:00:00', $completedReviews[0]->getDateCompleted());
    }

    public function testIntervalIncludesReviewsCompletedAnyTimeOfTheBoundaryDays()
    {
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 00:00:01');
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-17 23:59:58');

        $interval = new RCRClosedDateInterval('2026-01-15', '2026-01-17');
        $completedReviews = $this->dao->getCompletedReviews($this->contextId, $interval);

        $this->assertCount(2, $completedReviews);
    }

    public function testCompletedReviewCarriesTheDataTheReportNeeds()
    {
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 14:32:00');

        $completedReview = $this->dao->getCompletedReviews($this->contextId, null)[0];

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
}
