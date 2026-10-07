<?php

use APP\plugins\generic\reviewersControlReport\classes\RCRCompletedReview;
use APP\plugins\generic\reviewersControlReport\classes\ReviewersControlReportDAO;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\tests\PKPTestCase;

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

    public function testSubmissionTitleIsCarriedAsPlainTextWithoutItsInlineMarkup()
    {
        $dao = new TestableReviewersControlReportDAO();

        $review = $dao->getReviewsOfTheGrid([
            $this->completedReviewOfTitle('The <i>Homo sapiens</i> &lt;script&gt; case'),
        ])[0];

        $this->assertSame('The Homo sapiens <script> case', $review['title']);
    }

    public function testHostileSubmissionTitleIsNeverMarkup()
    {
        $dao = new TestableReviewersControlReportDAO();

        $review = $dao->getReviewsOfTheGrid([
            $this->completedReviewOfTitle('<img src=x onerror=alert(1)>'),
        ])[0];

        $this->assertStringNotContainsString('<img', $review['title']);
        $this->assertStringNotContainsString('onerror', $review['title']);
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
            ReviewAssignment::SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
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
