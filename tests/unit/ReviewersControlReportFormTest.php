<?php

use APP\plugins\generic\reviewersControlReport\classes\RCRCompletedReview;
use APP\plugins\generic\reviewersControlReport\classes\ReviewersControlReportForm;
use APP\plugins\generic\reviewersControlReport\classes\ReviewersReportBuilder;
use APP\plugins\generic\reviewersControlReport\classes\ReviewsReportBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PKP\form\Form;

require_once __DIR__ . '/../ReviewersControlReportTestCase.php';

class ReviewersControlReportFormTest extends ReviewersControlReportTestCase
{
    #[DataProvider('invalidDateProvider')]
    public function testMalformedNonexistentAndNonStringDatesBecomeFormErrors($field, $value)
    {
        $form = $this->createFormWithoutRequestValidators();
        $form->setData('startDateInterval', '2026-01-01');
        $form->setData('endDateInterval', '2026-12-31');
        $form->setData($field, $value);

        $this->assertFalse($form->validate(false));
        $this->assertArrayHasKey($field, $form->getErrorsArray());
        $this->assertSame(
            __('plugins.reports.reviewersControlReport.warning.invalidDate'),
            $form->getErrorsArray()[$field]
        );
        if (!is_string($value)) {
            $this->assertSame('', $form->getData($field));
        }
    }

    public static function invalidDateProvider(): array
    {
        return [
            'malformed beginning date' => ['startDateInterval', 'not-a-date'],
            'nonexistent ending date' => ['endDateInterval', '2026-02-31'],
            'wrongly formatted beginning date' => ['startDateInterval', '2026-2-03'],
            'date with surrounding whitespace' => ['startDateInterval', ' 2026-02-03 '],
            'date with trailing newline' => ['startDateInterval', "2026-02-03\n"],
            'array ending date' => ['endDateInterval', ['2026-02-03']],
        ];
    }

    public function testValidDatesStillBuildTheClosedInterval()
    {
        $form = new ReviewersControlReportForm(null, 'en', ['en']);
        $form->setData('startDateInterval', '2024-02-29');
        $form->setData('endDateInterval', '2026-12-31');
        $interval = $form->getDateInterval();

        $this->assertSame('2024-02-29 00:00:00', $interval->getBeginningDate());
        $this->assertSame('2026-12-31 23:59:59', $interval->getEndDate());
    }

    public function testInvalidDateCannotBuildAnIntervalWhenCalledDirectly()
    {
        $form = new ReviewersControlReportForm(null, 'en', ['en']);
        $form->setData('startDateInterval', '2026-02-31');
        $form->setData('endDateInterval', '2026-12-31');
        $this->expectException(\InvalidArgumentException::class);
        $form->getDateInterval();
    }

    public function testArrayReadFromRequestIsRejectedBeforeItIsCleanedForRedisplay()
    {
        $form = new class (null, 'en', ['en']) extends ReviewersControlReportForm {
            public function readUserVars($vars)
            {
                $this->setData('reportType', self::REPORT_TYPE_REVIEWS);
                $this->setData('startDateInterval', ['2026-01-01']);
                $this->setData('endDateInterval', '');
            }
        };
        $checks = new ReflectionProperty(Form::class, '_checks');
        $checks->setAccessible(true);
        $checks->setValue($form, []);

        $form->readInputData();
        $this->assertIsArray($form->getData('startDateInterval'));
        $this->assertFalse($form->validate(false));
        $this->assertArrayHasKey('startDateInterval', $form->getErrorsArray());
        $this->assertSame('', $form->getData('startDateInterval'));
    }

    private function createFormWithoutRequestValidators(): ReviewersControlReportForm
    {
        $form = new ReviewersControlReportForm(null, 'en', ['en']);
        $checks = new ReflectionProperty(Form::class, '_checks');
        $checks->setAccessible(true);
        $checks->setValue($form, []);
        return $form;
    }

    public function testFormulaLikeTextIsNeutralizedWhileNumbersKeepTheirTypes()
    {
        $form = new ReviewersControlReportForm(null, 'en', ['en']);
        $method = new ReflectionMethod($form, 'prepareCsvRow');
        $method->setAccessible(true);
        $row = $method->invoke($form, [
            '=1+1', '+cmd', '-2+3', '@SUM(A1:A2)', "\t=1+1", "\r=1+1",
            '  =1+1', "\0 =1+1", "\nordinary text", 'ordinary text', 42, 4.5,
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
        $form = new ReviewersControlReportForm(null, 'en', ['en']);
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
            null,
            4
        );
        $reviewerData = [11 => ['=1+1', 'reviewer@example.test', '@AFFILIATION', '+INTEREST']];
        $rows = [
            (new ReviewersReportBuilder())->getRows($reviewerData, [$review])[0],
            (new ReviewsReportBuilder())->getRows($reviewerData, [$review])[0],
        ];
        $form = new ReviewersControlReportForm(null, 'en', ['en']);
        $method = new ReflectionMethod($form, 'prepareCsvRow');
        $method->setAccessible(true);

        $reviewersRow = $method->invoke($form, $rows[0]);
        $reviewsRow = $method->invoke($form, $rows[1]);
        $this->assertSame("'=1+1", $reviewersRow[0]);
        $this->assertSame("'=EXTERNAL_REFERENCE()", $reviewsRow[1]);
        $this->assertSame("'@AFFILIATION", $reviewsRow[5]);
    }
}
