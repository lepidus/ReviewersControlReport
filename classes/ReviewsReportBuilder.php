<?php

namespace APP\plugins\generic\reviewersControlReport\classes;

use APP\plugins\generic\reviewersControlReport\classes\traits\RCRReportDate;
use PKP\submission\reviewAssignment\ReviewAssignment;

class ReviewsReportBuilder
{
    use RCRReportDate;

    public function getColumns(): array
    {
        return [
            __('plugins.reports.reviewersControlReport.field.submissionId'),
            __('plugins.reports.reviewersControlReport.field.submissionTitle'),
            __('plugins.reports.reviewersControlReport.field.reviewRound'),
            __('plugins.reports.reviewersControlReport.field.reviewerName'),
            __('plugins.reports.reviewersControlReport.field.email'),
            __('plugins.reports.reviewersControlReport.field.affiliation'),
            __('plugins.reports.reviewersControlReport.field.dateAssigned'),
            __('plugins.reports.reviewersControlReport.field.dateDue'),
            __('plugins.reports.reviewersControlReport.field.dateCompleted'),
            __('plugins.reports.reviewersControlReport.field.recommendation'),
            __('plugins.reports.reviewersControlReport.field.qualityRating'),
        ];
    }

    public function getRows(array $reviewersPersonalData, array $completedReviews): array
    {
        $rows = [];

        foreach ($completedReviews as $completedReview) {
            $personalData = $reviewersPersonalData[$completedReview->getReviewerId()] ?? ['', '', '', ''];

            // The review is what the row is about, so it leads: opening with
            // the reviewer made the report read like the one by reviewers
            $rows[] = [
                $completedReview->getSubmissionId(),
                $completedReview->getSubmissionTitle(),
                $completedReview->getRound(),
                $personalData[0],
                $personalData[1],
                $personalData[2],
                $this->formatReportDate($completedReview->getDateAssigned()),
                $this->formatReportDate($completedReview->getDateDue()),
                $this->formatReportDate($completedReview->getDateCompleted()),
                $this->getLocalizedRecommendation($completedReview->getRecommendation()),
                $completedReview->getQuality() ?? '',
            ];
        }

        return $rows;
    }

    private function getLocalizedRecommendation($recommendation): string
    {
        if (is_null($recommendation) || $recommendation === '') {
            return '';
        }

        $options = ReviewAssignment::getReviewerRecommendationOptions();
        if (!array_key_exists($recommendation, $options)) {
            return '';
        }

        return __($options[$recommendation]);
    }
}
