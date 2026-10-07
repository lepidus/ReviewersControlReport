<?php

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.submission.reviewAssignment.ReviewAssignment');
import('plugins.generic.reviewersControlReport.classes.RCRCompletedReview');
import('plugins.generic.reviewersControlReport.classes.ReviewersControlReportDAO');

class ReviewersGridContentSecurityTest extends PKPTestCase
{
    public function testReviewOfTheGridCarriesItsTitleLinkAndCompletionDate()
    {
        $dao = new TestableReviewersControlReportDAO();
        $dao->workflowUrl = 'https://example.test/workflow';

        $review = $dao->getReviewsOfTheGrid([$this->completedReviewOfTitle('Central do Brasil')])[0];

        $this->assertSame('Central do Brasil', $review['title']);
        $this->assertSame('https://example.test/workflow', $review['url']);
        $this->assertSame('2026-01-15', $review['dateCompleted']);
    }

    private function completedReviewOfTitle(string $title): RCRCompletedReview
    {
        return new RCRCompletedReview(
            11,
            100,
            $title,
            1,
            '2026-01-02 10:00:00',
            '2026-01-20 00:00:00',
            '2026-01-15 14:32:00',
            SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
            4
        );
    }
}

class TestableReviewersControlReportDAO extends ReviewersControlReportDAO
{
    public $workflowUrl;

    public function getSubmissionWorkflowUrl($submissionId, $submissionStageId)
    {
        return $this->workflowUrl;
    }
}
