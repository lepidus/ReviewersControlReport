<?php

import('plugins.generic.reviewersControlReport.classes.ReviewsSummary');

class ReviewersReportBuilder
{
    public function getColumns(): array
    {
        return [
            __('plugins.reports.reviewersControlReport.field.fullName'),
            __('plugins.reports.reviewersControlReport.field.email'),
            __('plugins.reports.reviewersControlReport.field.affiliation'),
            __('plugins.reports.reviewersControlReport.field.interests'),
            __('plugins.reports.reviewersControlReport.field.qualityAverage'),
            __('plugins.reports.reviewersControlReport.field.reviewedSubmissionsTotal'),
            __('plugins.reports.reviewersControlReport.field.reviewedSubmissionsTitles'),
        ];
    }

    public function getRows(array $reviewersPersonalData, array $completedReviews): array
    {
        $reviewsByReviewer = $this->groupReviewsByReviewer($completedReviews);
        $rows = [];

        foreach ($reviewersPersonalData as $reviewerId => $personalData) {
            $summary = new ReviewsSummary($reviewsByReviewer[$reviewerId] ?? []);
            $rows[] = array_merge($personalData, $this->getReviewsCells($summary));
        }

        return $rows;
    }

    private function getReviewsCells(ReviewsSummary $summary): array
    {
        if ($summary->isEmpty()) {
            return ['', '', ''];
        }

        return [
            $summary->getQualityAverage(),
            $summary->getTotal(),
            implode("\n", $summary->getSubmissionTitles()),
        ];
    }

    private function groupReviewsByReviewer(array $completedReviews): array
    {
        $reviewsByReviewer = [];
        foreach ($completedReviews as $completedReview) {
            $reviewsByReviewer[$completedReview->getReviewerId()][] = $completedReview;
        }

        return $reviewsByReviewer;
    }
}
