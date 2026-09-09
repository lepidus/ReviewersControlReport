<?php

trait ReviewerData
{
    public function getReviewerData($reviewerId, $completedReviews): array
    {
        $reviewerData = array_merge(
            $this->getReviewerPersonalData($reviewerId),
            $this->getReviewerReviewsData($completedReviews)
        );

        return $reviewerData;
    }

    public function getReviewerPersonalData($reviewerId): array
    {
        $userDao = DAORegistry::getDAO('UserDAO');
        $reviewer = $userDao->getById($reviewerId);

        return [
            $reviewer->getLocalizedGivenName() . ' ' . $reviewer->getLocalizedFamilyName(),
            $reviewer->getEmail(),
            $reviewer->getLocalizedAffiliation(),
            $reviewer->getInterestString()
        ];
    }

    private function getReviewerReviewsData($completedReviews): array
    {
        $reviewsSummary = new ReviewsSummary($completedReviews);

        $fullSubmissionsText = "";
        foreach ($completedReviews as $completedReview) {
            $submissionTitle = $completedReview->getSubmissionTitle();
            $dateCompleted = date("Y-m-d", strtotime($completedReview->getDateCompleted()));
            $fullSubmissionsText .= "{$submissionTitle}. " . __('common.completed.date', ['dateCompleted' => $dateCompleted]) . "\n";
        }

        return [
            $reviewsSummary->getQualityAverage(),
            $reviewsSummary->getTotal() > 0 ? $reviewsSummary->getTotal() : "",
            $fullSubmissionsText
        ];
    }
}
