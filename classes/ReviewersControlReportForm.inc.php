<?php

import('lib.pkp.classes.form.Form');
import('plugins.generic.reviewersControlReport.classes.ReviewersControlReportDAO');

class ReviewersControlReportForm extends Form
{
    public function generateReport($reviewersId)
    {
        header('content-type: text/comma-separated-values');
        header("content-disposition: attachment; filename=reviewersControlReport-" . date('Ymd') . '.csv');

        $columns = array(
            __('plugins.reports.reviewersControlReport.field.fullName'),
            __('plugins.reports.reviewersControlReport.field.email'),
            __('plugins.reports.reviewersControlReport.field.affiliation'),
            __('plugins.reports.reviewersControlReport.field.interests'),
            __('plugins.reports.reviewersControlReport.field.qualityAverage'),
            __('plugins.reports.reviewersControlReport.field.reviewedSubmissionsTotal'),
            __('plugins.reports.reviewersControlReport.field.reviewedSubmissionsTitleAndCompletedDate')
        );

        $fp = fopen('php://output', 'wt');
        fputcsv($fp, $columns);

        $reviewersDao = new ReviewersControlReportDAO();

        foreach ($reviewersId as $id) {
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

            fputcsv($fp, $reviewersData);
        }

        fclose($fp);
    }
}
