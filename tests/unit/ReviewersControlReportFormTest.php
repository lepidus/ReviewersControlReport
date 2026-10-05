<?php

use APP\plugins\generic\reviewersControlReport\classes\ReviewersControlReportForm;
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
        $checks->setValue($form, []);
        return $form;
    }
}
