<?php

use APP\plugins\generic\reviewersControlReport\classes\RCRCompletedReview;
use APP\plugins\generic\reviewersControlReport\classes\ReviewersControlReportForm;
use APP\plugins\generic\reviewersControlReport\classes\ReviewersReportBuilder;
use APP\plugins\generic\reviewersControlReport\classes\ReviewsReportBuilder;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\tests\PKPTestCase;

class ReviewersControlReportCsvSecurityTest extends PKPTestCase
{
    public function testFormulaLikeTextIsNeutralizedWhileNumbersKeepTheirTypes()
    {
        $form = new ReviewersControlReportForm();
        $method = new ReflectionMethod($form, 'prepareCsvRow');
        $method->setAccessible(true);

        $row = $method->invoke($form, [
            '=1+1',
            '+cmd',
            '-2+3',
            '@SUM(A1:A2)',
            "\t=1+1",
            "\r=1+1",
            '  =1+1',
            "\0 =1+1",
            "\nordinary text",
            'ordinary text',
            42,
            4.5,
        ]);

        $this->assertSame("'=1+1", $row[0]);
        $this->assertSame("'+cmd", $row[1]);
        $this->assertSame("'-2+3", $row[2]);
        $this->assertSame("'@SUM(A1:A2)", $row[3]);
        $this->assertSame("'\t=1+1", $row[4]);
        $this->assertSame("'\r=1+1", $row[5]);
        $this->assertSame("'  =1+1", $row[6]);
        $this->assertSame("'\0 =1+1", $row[7]);
        $this->assertSame("'\nordinary text", $row[8]);
        $this->assertSame('ordinary text', $row[9]);
        $this->assertSame(42, $row[10]);
        $this->assertSame(4.5, $row[11]);
    }

    public function testCsvWriterUsesStandardQuoteEscaping()
    {
        $form = new ReviewersControlReportForm();
        $method = new ReflectionMethod($form, 'writeCsvRow');
        $method->setAccessible(true);
        $stream = fopen('php://memory', 'w+');

        $method->invoke($form, $stream, ['text \\"quoted"']);
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        $this->assertSame(['text \\"quoted"'], str_getcsv($csv, ',', '"', ''));
    }

    public function testUserControlledTextFromBothReportTypesIsNeutralized()
    {
        $review = new RCRCompletedReview(
            11,
            100,
            '=EXTERNAL_REFERENCE()',
            1,
            '2026-01-02 10:00:00',
            '2026-01-20 00:00:00',
            '2026-01-15 14:32:00',
            ReviewAssignment::SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
            4
        );
        $reviewerData = [11 => ['=1+1', 'reviewer@example.test', '@AFFILIATION', '+INTEREST']];
        $rows = [
            (new ReviewersReportBuilder())->getRows($reviewerData, [$review])[0],
            (new ReviewsReportBuilder())->getRows($reviewerData, [$review])[0],
        ];
        $form = new ReviewersControlReportForm();
        $method = new ReflectionMethod($form, 'prepareCsvRow');
        $method->setAccessible(true);

        $reviewersRow = $method->invoke($form, $rows[0]);
        $reviewsRow = $method->invoke($form, $rows[1]);

        $this->assertSame("'=1+1", $reviewersRow[0]);
        $this->assertSame("'=EXTERNAL_REFERENCE()", $reviewsRow[1]);
        $this->assertSame("'@AFFILIATION", $reviewsRow[5]);
    }
}
