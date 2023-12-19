<?php

trait ReviewerData
{
    public function getReviewerData($id, $reviewersDao): array
    {
        $reviewersData = [];
        $userDao = DAORegistry::getDAO('UserDAO');
        $user = $userDao->getById($id);

        $reviewersData[] = $user->getLocalizedGivenName() . $user->getLocalizedFamilyName();
        $reviewersData[] = $user->getEmail();
        $reviewersData[] = $user->getLocalizedAffiliation();
        $reviewersData[] = $user->getInterestString();
        $rating = $reviewersDao->getQualityAverage($user->getId());
        $completedSubmissions = $reviewersDao->getTotalReviewedSubmissions($user->getId());
        $reviewersData[] = $rating > 0 ? $rating : "";
        $reviewersData[] = $completedSubmissions > 0 ? $completedSubmissions : "";
        $isCsv = true;
        $reviewedSubmissionsTitleAndDate = $reviewersDao->getReviewedSubmissionsTitleAndDate($user->getId(), $isCsv);
        $fullSubmissionsText = "";

        foreach ($reviewedSubmissionsTitleAndDate as $submission) {
            $fullSubmissionsText .= $submission[0] . ". " . $submission[1] . "\n";
        }

        $reviewersData[] = $fullSubmissionsText;

        return $reviewersData;
    }
}
