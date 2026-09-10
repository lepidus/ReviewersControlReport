<?php

import('lib.pkp.tests.DatabaseTestCase');
import('classes.submission.Submission');
import('classes.publication.Publication');
import('lib.pkp.classes.user.User');
import('lib.pkp.classes.submission.reviewAssignment.ReviewAssignment');
import('plugins.generic.reviewersControlReport.classes.ClosedDateInterval');
import('plugins.generic.reviewersControlReport.classes.ReviewersControlReportDAO');

class ReviewersControlReportDAOTest extends DatabaseTestCase
{
    private $dao;
    private $locale = 'en_US';
    // Context ids of their own, so the reviews seeded in the test database
    // (all under journal 1) do not leak into the assertions
    private $contextId = 9001;
    private $otherContextId = 9002;
    private $reviewerId;
    private $submissionOfContext;
    private $submissionOfOtherContext;

    public function setUp(): void
    {
        parent::setUp();
        $this->dao = new ReviewersControlReportDAO();
        $this->reviewerId = $this->createReviewer();
        $this->submissionOfContext = $this->createSubmission($this->contextId, 'Central do Brasil');
        $this->submissionOfOtherContext = $this->createSubmission($this->otherContextId, 'Cidade de Deus');
    }

    protected function getAffectedTables()
    {
        return ['submissions', 'submission_settings', 'publications', 'publication_settings',
            'review_assignments', 'review_rounds', 'users', 'user_settings'];
    }

    private function createReviewer(): int
    {
        $user = new User();
        $user->setData('givenName', [$this->locale => 'Walter']);
        $user->setData('familyName', [$this->locale => 'Salles']);
        $user->setData('email', 'walter.salles@ancine.com.br');
        $user->setData('username', 'walter.salles');
        $user->setData('password', 'walter.salles');

        return DAORegistry::getDAO('UserDAO')->insertObject($user);
    }

    private function createSubmission($contextId, $title): int
    {
        $submission = new Submission();
        $submission->setData('contextId', $contextId);
        $submission->setData('status', STATUS_QUEUED);
        $submission->setData('locale', $this->locale);
        $submissionId = DAORegistry::getDAO('SubmissionDAO')->insertObject($submission);

        $publication = new Publication();
        $publication->setData('submissionId', $submissionId);
        $publication->setData('title', $title, $this->locale);
        $publicationId = DAORegistry::getDAO('PublicationDAO')->insertObject($publication);

        $submission->setData('currentPublicationId', $publicationId);
        DAORegistry::getDAO('SubmissionDAO')->updateObject($submission);

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
        $reviewAssignment->setDateDue('2026-01-20 00:00:00');
        $reviewAssignment->setDateCompleted($dateCompleted);
        $reviewAssignment->setQuality($overrides['quality'] ?? 4);
        $reviewAssignment->setRecommendation($overrides['recommendation'] ?? SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT);
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

        $interval = new ClosedDateInterval('2026-03-01', '2026-03-31');
        $completedReviews = $this->dao->getCompletedReviews($this->contextId, $interval);

        $this->assertCount(1, $completedReviews);
        $this->assertEquals('2026-03-20 09:00:00', $completedReviews[0]->getDateCompleted());
    }

    public function testIntervalIncludesReviewsCompletedAnyTimeOfTheBoundaryDays()
    {
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-15 00:00:01');
        $this->createReviewAssignment($this->submissionOfContext, '2026-01-17 23:59:58');

        $interval = new ClosedDateInterval('2026-01-15', '2026-01-17');
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
        $this->assertEquals(SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT, $completedReview->getRecommendation());
        $this->assertEquals(4, $completedReview->getQuality());
    }
}
