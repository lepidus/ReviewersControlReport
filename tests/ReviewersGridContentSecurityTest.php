<?php

use APP\plugins\generic\reviewersControlReport\classes\RCRCompletedReview;
use APP\plugins\generic\reviewersControlReport\classes\ReviewersControlReportDAO;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\tests\PKPTestCase;

class ReviewersGridContentSecurityTest extends PKPTestCase
{
    public function testSubmissionTitleAndWorkflowUrlAreEscapedInGridHtml()
    {
        $dao = new TestableReviewersControlReportDAO();
        $dao->workflowUrl = 'https://example.test/workflow?value=" onclick="alert(2)&other=1';
        $completedReview = new RCRCompletedReview(
            11,
            100,
            '<img src=x onerror=alert(1)>',
            1,
            '2026-01-02 10:00:00',
            '2026-01-20 00:00:00',
            '2026-01-15 14:32:00',
            ReviewAssignment::SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
            4
        );

        $method = new ReflectionMethod($dao, 'getReviewsGridCells');
        $method->setAccessible(true);
        $cells = $method->invoke($dao, [$completedReview]);
        $html = $cells[0][0];

        $this->assertStringContainsString(
            'href="https://example.test/workflow?value=&quot; onclick=&quot;alert(2)&amp;other=1"',
            $html
        );
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function testInlineMarkupOfSubmissionTitleIsShownAsPlainText()
    {
        $dao = new TestableReviewersControlReportDAO();
        $dao->workflowUrl = 'https://example.test/workflow';
        $completedReview = new RCRCompletedReview(
            11,
            100,
            'The <i>Homo sapiens</i> &lt;script&gt; case',
            1,
            '2026-01-02 10:00:00',
            '2026-01-20 00:00:00',
            '2026-01-15 14:32:00',
            ReviewAssignment::SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
            4
        );

        $method = new ReflectionMethod($dao, 'getReviewsGridCells');
        $method->setAccessible(true);
        $html = $method->invoke($dao, [$completedReview])[0][0];

        $this->assertStringContainsString('>The Homo sapiens &lt;script&gt; case</a>', $html);
        $this->assertStringNotContainsString('<i>', $html);
        $this->assertStringNotContainsString('<script>', $html);
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
