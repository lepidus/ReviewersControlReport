<?php

use APP\facades\Repo;
use APP\core\Application;
use APP\core\PageRouter;
use APP\journal\Journal;
use APP\plugins\generic\reviewersControlReport\classes\RCRClosedDateInterval;
use APP\plugins\generic\reviewersControlReport\classes\ReviewersControlReportDAO;
use APP\publication\Publication;
use APP\submission\Submission;
use Illuminate\Support\Facades\DB;
use PKP\db\DAORegistry;
use PKP\db\DBResultRange;
use PKP\security\Role;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\tests\DatabaseTestCase;
use PKP\user\User;

class ReviewersControlReportDAOTest extends DatabaseTestCase
{
    private $dao;
    private $locale = 'en';
    // Context ids of their own, so the reviews seeded in the test database
    // (all under journal 1) do not leak into the assertions
    private $contextId;
    private $otherContextId;
    private $reviewerId;
    private $submissionOfContext;
    private $submissionOfOtherContext;

    public function setUp(): void
    {
        parent::setUp();
        $request = Application::get()->getRequest();
        if (is_null($request->getRouter())) {
            $request->setRouter(new PageRouter());
        }
        DB::beginTransaction();
        $this->dao = new ReviewersControlReportDAO();
        $this->contextId = $this->createContext('reviewers-report-primary');
        $this->otherContextId = $this->createContext('reviewers-report-other');
        $this->reviewerId = $this->createReviewer();
        $this->submissionOfContext = $this->createSubmission($this->contextId, 'Central do Brasil');
        $this->submissionOfOtherContext = $this->createSubmission($this->otherContextId, 'Cidade de Deus');
    }

    protected function getAffectedTables()
    {
        return [];
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    private function createContext(string $path): int
    {
        $journal = new Journal();
        $journal->setPath(substr($path, 0, 4) . uniqid());
        $journal->setPrimaryLocale($this->locale);
        $journal->setEnabled(true);
        $journal->setSequence(1);
        $journal->setName($path, $this->locale);

        return DAORegistry::getDAO('JournalDAO')->insertObject($journal);
    }

    private function createReviewer(): int
    {
        $suffix = uniqid();
        $user = new User();
        $user->setGivenName('Walter', $this->locale);
        $user->setFamilyName('Salles', $this->locale);
        $user->setEmail('rcr.' . $suffix . '@example.test');
        $user->setUsername('rcr' . $suffix);
        $user->setPassword('walter.salles');
        $user->setDateRegistered('2026-01-01 00:00:00');

        return Repo::user()->add($user);
    }

    private function createSubmission($contextId, $title): int
    {
        $submission = new Submission();
        $submission->setData('contextId', $contextId);
        $submission->setData('status', Submission::STATUS_QUEUED);
        $submission->setData('locale', $this->locale);
        $submissionId = Repo::submission()->dao->insert($submission);

        $publication = new Publication();
        $publication->setData('submissionId', $submissionId);
        $publication->setData('title', $title, $this->locale);
        $publicationId = Repo::publication()->add($publication);

        Repo::submission()->edit($submission, ['currentPublicationId' => $publicationId]);

        return $submissionId;
    }

    private function createReviewAssignment($submissionId, $dateCompleted, $overrides = []): void
    {
        $reviewRoundId = DAORegistry::getDAO('ReviewRoundDAO')
            ->build($submissionId, WORKFLOW_STAGE_ID_EXTERNAL_REVIEW, 1)
            ->getId();

        $reviewAssignment = new ReviewAssignment();
        $reviewAssignment->setSubmissionId($submissionId);
        $reviewAssignment->setReviewerId($overrides['reviewerId'] ?? $this->reviewerId);
        $reviewAssignment->setReviewRoundId($reviewRoundId);
        $reviewAssignment->setStageId(WORKFLOW_STAGE_ID_EXTERNAL_REVIEW);
        $reviewAssignment->setRound(1);
        $reviewAssignment->setDateAssigned('2026-01-02 10:00:00');
        $reviewAssignment->setDateResponseDue('2026-01-10 00:00:00');
        $reviewAssignment->setDateDue('2026-01-20 00:00:00');
        $reviewAssignment->setDateCompleted($dateCompleted);
        $reviewAssignment->setQuality($overrides['quality'] ?? 4);
        $reviewAssignment->setRecommendation(
            $overrides['recommendation'] ?? ReviewAssignment::SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT
        );
        $reviewAssignment->setDeclined($overrides['declined'] ?? 0);
        $reviewAssignment->setCancelled($overrides['cancelled'] ?? 0);

        DAORegistry::getDAO('ReviewAssignmentDAO')->insertObject($reviewAssignment);
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
        $grid = $this->dao->getReviewers($this->contextId, new DBResultRange(1, 1));
        $this->assertSame(2, $grid->getCount());
        $this->assertArrayHasKey($this->reviewerId, $grid->toArray());
        $this->assertCount(1, $grid->toArray());
        $secondPage = $this->dao->getReviewers($this->contextId, new DBResultRange(1, 2));
        $this->assertSame(2, $secondPage->getPage());
        $this->assertArrayHasKey($activeReviewerId, $secondPage->toArray());
        $this->assertCount(1, $secondPage->toArray());
        $this->assertSame([], $this->dao->getReviewersIds($this->otherContextId));
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
