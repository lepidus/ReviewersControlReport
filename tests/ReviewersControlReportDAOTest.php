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

require_once __DIR__ . '/ReviewersControlReportTestCase.php';

class ReviewersControlReportDAOTest extends ReviewersControlReportTestCase
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
        $request = Application::get()->getRequest();
        if (is_null($request->getRouter())) {
            $request->setRouter(new PageRouter());
        }
        DB::beginTransaction();
        $this->dao = new ReviewersControlReportDAO();
        $this->contextId = $this->createContext('rcr-primary');
        $this->otherContextId = $this->createContext('rcr-secondary');
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
        $user = Repo::user()->newDataObject();
        $user->setData('givenName', [$this->locale => 'Walter']);
        $user->setData('familyName', [$this->locale => 'Salles']);
        $user->setData('affiliation', [$this->locale => 'Agência Nacional do Cinema']);
        $user->setData('email', 'walter.salles@ancine.com.br');
        $user->setData('userName', 'walter.salles');
        $user->setData('password', 'walter.salles');
        $user->setData('dateRegistered', '2026-01-01 00:00:00');

        return Repo::user()->add($user);
    }

    private function createContext(string $path): int
    {
        $context = Application::getContextDAO()->newDataObject();
        $context->setData('urlPath', $path);
        $context->setData('enabled', true);
        $context->setData('seq', 1);
        $context->setData('primaryLocale', $this->locale);
        $context->setData('supportedLocales', [$this->locale]);
        $context->setData('name', [$this->locale => $path]);
        $context->setData('contactName', 'Reviewers Control Report');
        $context->setData('contactEmail', 'reviewers-control@example.test');

        return Application::getContextDAO()->insertObject($context);
    }

    private function createSubmission($contextId, $title): int
    {
        $submission = Repo::submission()->newDataObject([
            'contextId' => $contextId,
            'status' => PKPSubmission::STATUS_QUEUED,
            'locale' => $this->locale,
        ]);
        $submissionId = Repo::submission()->dao->insert($submission);

        $publication = Repo::publication()->newDataObject([
            'submissionId' => $submissionId,
            'title' => [$this->locale => $title],
        ]);
        $publicationId = Repo::publication()->add($publication);

        Repo::submission()->edit($submission, ['currentPublicationId' => $publicationId]);

        return $submissionId;
    }

    private function createReviewAssignment($submissionId, $dateCompleted, $overrides = []): void
    {
        $reviewRoundId = DAORegistry::getDAO('ReviewRoundDAO')
            ->build($submissionId, WORKFLOW_STAGE_ID_EXTERNAL_REVIEW, 1)
            ->getId();

        $reviewAssignment = Repo::reviewAssignment()->newDataObject();
        $reviewAssignment->setSubmissionId($submissionId);
        $reviewAssignment->setReviewerId($overrides['reviewerId'] ?? $this->reviewerId);
        $reviewAssignment->setReviewRoundId($reviewRoundId);
        $reviewAssignment->setStageId(WORKFLOW_STAGE_ID_EXTERNAL_REVIEW);
        $reviewAssignment->setRound(1);
        $reviewAssignment->setDateAssigned('2026-01-02 10:00:00');
        $reviewAssignment->setDateResponseDue('2026-01-10 00:00:00');
        $reviewAssignment->setDateConfirmed('2026-01-03 00:00:00');
        $reviewAssignment->setDateDue('2026-01-20 00:00:00');
        $reviewAssignment->setDateCompleted($dateCompleted);
        $reviewAssignment->setQuality($overrides['quality'] ?? 4);
        $reviewAssignment->setRecommendation(
            $overrides['recommendation'] ?? ReviewAssignment::SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT
        );
        $reviewAssignment->setDeclined($overrides['declined'] ?? 0);
        $reviewAssignment->setCancelled($overrides['cancelled'] ?? 0);

        Repo::reviewAssignment()->add($reviewAssignment);
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
        $reviewerGroup = Repo::userGroup()->getByRoleIds([Role::ROLE_ID_REVIEWER], 1)->first();
        $this->assertNotNull($reviewerGroup);
        Repo::userGroup()->assignUserToGroup($this->reviewerId, $reviewerGroup->id);

        $locale = Locale::getFacadeRoot();
        $property = new ReflectionProperty($locale, 'locale');
        $property->setAccessible(true);
        $property->setValue($locale, 'pt_BR');

        $reviewers = $this->dao->getReviewers(1);

        $this->assertArrayHasKey($this->reviewerId, $reviewers);
        $this->assertSame('Agência Nacional do Cinema', $reviewers[$this->reviewerId]->getAffiliation());
    }
}
