<?php

trait ReviewerData
{
    public function getReviewerData($reviewerId, $reviewersDao): array
    {
        $reviewerData = array_merge(
            $this->getReviewerPersonalData($reviewerId),
            $this->getReviewerReviewsData($reviewerId, $reviewersDao)
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

    private function getReviewerReviewsData($reviewerId, $reviewersDao): array
    {
        $rating = $reviewersDao->getQualityAverage($reviewerId);
        $completedSubmissions = $reviewersDao->getTotalReviewedSubmissions($reviewerId);
        $isCsv = true;
        $reviewedSubmissionsTitleAndDate = $reviewersDao->getReviewedSubmissionsTitleAndDate($reviewerId, $isCsv);

        $fullSubmissionsText = "";
        foreach ($reviewedSubmissionsTitleAndDate as [$title, $dateCompleted]) {
            $fullSubmissionsText .= "{$title}. {$dateCompleted}\n";
        }

        return [
            $rating > 0 ? $rating : "",
            $completedSubmissions > 0 ? $completedSubmissions : "",
            $fullSubmissionsText
        ];
    }
}
