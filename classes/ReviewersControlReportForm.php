<?php

namespace APP\plugins\generic\reviewersControlReport\classes;

use APP\plugins\generic\reviewersControlReport\classes\traits\RCRReviewerData;
use InvalidArgumentException;
use PKP\form\Form;
use PKP\form\validation\FormValidatorCSRF;
use PKP\form\validation\FormValidatorPost;

class ReviewersControlReportForm extends Form
{
    use RCRReviewerData;

    public const REPORT_TYPE_REVIEWERS = 'reviewers';
    public const REPORT_TYPE_REVIEWS = 'reviews';

    private const EARLIEST_DATE = '1000-01-01';
    private const LATEST_DATE = '9999-12-31';

    public function __construct($plugin = null, ?string $requiredLocale = null, ?array $supportedLocales = null)
    {
        $template = is_null($plugin) ? null : $plugin->getTemplateResource('index_component.tpl');
        parent::__construct($template, true, $requiredLocale, $supportedLocales);

        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));
    }

    public function initData()
    {
        $this->setData('reportType', self::REPORT_TYPE_REVIEWERS);
        $this->setData('startDateInterval', '');
        $this->setData('endDateInterval', '');
    }

    public function readInputData()
    {
        $this->readUserVars(['reportType', 'startDateInterval', 'endDateInterval']);
    }

    public function validate($callHooks = true)
    {
        $isValid = parent::validate($callHooks);

        foreach (['startDateInterval', 'endDateInterval'] as $field) {
            $date = $this->getData($field);
            if (!$this->isValidDateInput($date)) {
                if ($date !== null && !is_string($date)) {
                    $this->setData($field, '');
                }
                $this->addError($field, __('plugins.reports.reviewersControlReport.warning.invalidDate'));
                $this->addErrorField($field);
                $isValid = false;
            }
        }

        if (!$isValid) {
            return false;
        }

        $dateInterval = $this->getDateInterval();
        if ($dateInterval !== null && !$dateInterval->isValid()) {
            $this->addError(
                'startDateInterval',
                __('plugins.reports.reviewersControlReport.warning.invalidDateInterval')
            );
            $this->addErrorField('startDateInterval');
            $isValid = false;
        }

        return $isValid;
    }

    public function getDateInterval(): ?RCRClosedDateInterval
    {
        $startDateInput = $this->getData('startDateInterval');
        $endDateInput = $this->getData('endDateInterval');
        if (!$this->isValidDateInput($startDateInput) || !$this->isValidDateInput($endDateInput)) {
            throw new InvalidArgumentException('Dates must be valid strings in the YYYY-MM-DD format.');
        }

        $startDate = $this->normalizeDateInput($startDateInput);
        $endDate = $this->normalizeDateInput($endDateInput);
        if ($startDate === '' && $endDate === '') {
            return null;
        }

        return new RCRClosedDateInterval(
            $startDate === '' ? self::EARLIEST_DATE : $startDate,
            $endDate === '' ? self::LATEST_DATE : $endDate
        );
    }

    public function generateReport($request): void
    {
        $contextId = $request->getContext()->getId();
        $reviewersDao = new ReviewersControlReportDAO();
        $completedReviews = $reviewersDao->getCompletedReviews($contextId, $this->getDateInterval());
        $reviewersPersonalData = $this->isReviewsReport()
            ? $this->getReviewersPersonalDataOfReviews($completedReviews)
            : $this->getReviewersPersonalData($reviewersDao->getReviewersIds($contextId));
        $reportBuilder = $this->getReportBuilder();

        $this->emitHttpHeaders();
        $csvWriter = new RCRCsvWriter();
        $csvFile = fopen('php://output', 'wt');
        $csvWriter->writeRow($csvFile, $reportBuilder->getColumns());
        foreach ($reportBuilder->getRows($reviewersPersonalData, $completedReviews) as $row) {
            $csvWriter->writeRow($csvFile, $row);
        }
        fclose($csvFile);
    }

    private function isValidDateInput($date): bool
    {
        if ($date === null || $date === '') {
            return true;
        }
        if (!is_string($date) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})\z/', $date, $matches)) {
            return false;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }

    private function normalizeDateInput($date): string
    {
        if ($date === null) {
            return '';
        }
        if (!is_string($date)) {
            throw new InvalidArgumentException('Date input must be a string.');
        }

        return trim($date);
    }

    private function isReviewsReport(): bool
    {
        return $this->getData('reportType') === self::REPORT_TYPE_REVIEWS;
    }

    private function getReportBuilder()
    {
        return $this->isReviewsReport() ? new ReviewsReportBuilder() : new ReviewersReportBuilder();
    }

    private function emitHttpHeaders(): void
    {
        header('content-type: text/comma-separated-values');
        header('content-disposition: attachment; filename=' . $this->getFileName());
    }

    private function getFileName(): string
    {
        $report = $this->isReviewsReport() ? 'reviewsControlReport' : 'reviewersControlReport';
        $startDate = $this->getFileNameDate($this->getData('startDateInterval'));
        $endDate = $this->getFileNameDate($this->getData('endDateInterval'));

        if ($startDate && $endDate) {
            return $report . '-' . $startDate . '-' . $endDate . '.csv';
        }
        if ($startDate) {
            return $report . '-from-' . $startDate . '.csv';
        }
        if ($endDate) {
            return $report . '-until-' . $endDate . '.csv';
        }

        return $report . '-' . date('Ymd') . '.csv';
    }

    private function getFileNameDate($date): string
    {
        $date = $this->normalizeDateInput($date);
        return $date === '' ? '' : date('Ymd', strtotime($date));
    }
}
