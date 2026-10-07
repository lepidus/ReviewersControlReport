<?php

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.submission.reviewAssignment.ReviewAssignment');
import('plugins.generic.reviewersControlReport.classes.RCRCompletedReview');
import('plugins.generic.reviewersControlReport.classes.RCRCsvWriter');
import('plugins.generic.reviewersControlReport.classes.ReviewersReportBuilder');
import('plugins.generic.reviewersControlReport.classes.ReviewsReportBuilder');

class RCRCsvWriterTest extends PKPTestCase
{
    public function testFormulaLikeTextIsNeutralizedWhileNumbersKeepTheirTypes()
    {
        $csvWriter = new RCRCsvWriter();

        $row = $csvWriter->neutralizeFormulas([
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

    public function testRowRoundTripsOrdinaryFields()
    {
        $csvWriter = new RCRCsvWriter();
        $stream = fopen('php://memory', 'w+');
        $row = ['plain', 'with space', 'with,comma', 'quoted "text"', "line\nbreak", 42];

        $csvWriter->writeRow($stream, $row);
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        $this->assertSame(array_map('strval', $row), str_getcsv($csv));
    }

    public function testRowIsWrittenAsStandardBytesKeepingBackslashes()
    {
        $csvWriter = new RCRCsvWriter();
        $stream = fopen('php://memory', 'w+');

        $csvWriter->writeRow($stream, ['plain', 'with space', 'with,comma', "line\nbreak", "\ttab", 42, 4.5]);
        $csvWriter->writeRow($stream, ['text \\"quoted"']);
        $csvWriter->writeRow($stream, ["\0 =1+1"]);
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        $firstRow = 'plain,"with space","with,comma","line' . "\n" . 'break","\'' . "\t" . 'tab",42,4.5' . "\n";
        $this->assertSame($firstRow, substr($csv, 0, strlen($firstRow)));
        $this->assertStringContainsString('2274657874205c222271756f7465642222220a', bin2hex($csv));
        $this->assertStringEndsWith('222700203d312b31220a', bin2hex($csv));
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
            SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT,
            4
        );
        $reviewerData = [11 => ['=1+1', 'reviewer@example.test', '@AFFILIATION', '+INTEREST']];
        $rows = [
            (new ReviewersReportBuilder())->getRows($reviewerData, [$review])[0],
            (new ReviewsReportBuilder())->getRows($reviewerData, [$review])[0],
        ];
        $csvWriter = new RCRCsvWriter();

        $reviewersRow = $csvWriter->neutralizeFormulas($rows[0]);
        $reviewsRow = $csvWriter->neutralizeFormulas($rows[1]);

        $this->assertSame("'=1+1", $reviewersRow[0]);
        $this->assertSame("'=EXTERNAL_REFERENCE()", $reviewsRow[1]);
        $this->assertSame("'@AFFILIATION", $reviewsRow[5]);
    }
}
