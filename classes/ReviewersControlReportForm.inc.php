<?php

import('lib.pkp.classes.form.Form');
import('plugins.generic.reviewersControlReport.classes.RCRClosedDateInterval');
import('plugins.generic.reviewersControlReport.classes.ReviewersControlReportDAO');
import('plugins.generic.reviewersControlReport.classes.ReviewersReportBuilder');
import('plugins.generic.reviewersControlReport.classes.ReviewsReportBuilder');
import('plugins.generic.reviewersControlReport.classes.traits.RCRReviewerData');

class ReviewersControlReportForm extends Form
{
    use RCRReviewerData;

    public const REPORT_TYPE_REVIEWERS = 'reviewers';
    public const REPORT_TYPE_REVIEWS = 'reviews';

    // An interval open on one side is still expressed as a closed one, so that
    // filling in a single date does not require a second kind of interval
    private const EARLIEST_DATE = '1000-01-01';
    private const LATEST_DATE = '9999-12-31';

    public function __construct($plugin = null)
    {
        $template = is_null($plugin) ? null : $plugin->getTemplateResource('index_component.tpl');
        parent::__construct($template);

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

        $dateInterval = $this->getDateInterval();
        if (!is_null($dateInterval) && !$dateInterval->isValid()) {
            $this->addError('startDateInterval', __('plugins.reports.reviewersControlReport.warning.invalidDateInterval'));
            $this->addErrorField('startDateInterval');
            $isValid = false;
        }

        return $isValid;
    }

    /**
     * The period chosen by the user, or null when no date was filled in and
     * the report should cover every completed review.
     */
    public function getDateInterval()
    {
        $startDate = trim((string) $this->getData('startDateInterval'));
        $endDate = trim((string) $this->getData('endDateInterval'));

        if ($startDate === '' && $endDate === '') {
            return null;
        }

        return new RCRClosedDateInterval(
            $startDate === '' ? self::EARLIEST_DATE : $startDate,
            $endDate === '' ? self::LATEST_DATE : $endDate
        );
    }

    public function generateReport($request)
    {
        $contextId = $request->getContext()->getId();
        $reviewersDao = new ReviewersControlReportDAO();

        $completedReviews = $reviewersDao->getCompletedReviews($contextId, $this->getDateInterval());
        $reviewersPersonalData = $this->isReviewsReport()
            ? $this->getReviewersPersonalDataOfReviews($completedReviews)
            : $this->getReviewersPersonalData($reviewersDao->getReviewersIds($contextId));
        $reportBuilder = $this->getReportBuilder();

        $this->emitHttpHeaders();

        $csvFile = fopen('php://output', 'wt');
        $this->writeCsvRow($csvFile, $reportBuilder->getColumns());
        foreach ($reportBuilder->getRows($reviewersPersonalData, $completedReviews) as $row) {
            $this->writeCsvRow($csvFile, $row);
        }
        fclose($csvFile);
    }

    private function writeCsvRow($csvFile, array $row): void
    {
        fputcsv($csvFile, $this->prepareCsvRow($row), ',', '"', '');
    }

    private function prepareCsvRow(array $row): array
    {
        return array_map(function ($cell) {
            if (is_string($cell) && preg_match('/^(?:[\\x00-\\x20]*[=+\\-@]|[\\t\\r\\n])/', $cell)) {
                return "'" . $cell;
            }

            return $cell;
        }, $row);
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

    /**
     * Names the file after what is inside it: which report, and which period.
     * Without a period there is nothing to state but the day it was taken.
     */
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
        $date = trim((string) $date);

        return $date === '' ? '' : date('Ymd', strtotime($date));
    }
}
