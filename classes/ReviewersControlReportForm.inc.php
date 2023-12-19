<?php

import('lib.pkp.classes.form.Form');
import('plugins.generic.reviewersControlReport.classes.ReviewersControlReportDAO');
import('plugins.generic.reviewersControlReport.classes.traits.ReviewerData');

class ReviewersControlReportForm extends Form
{
    use ReviewerData;

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
            $reviewersData = $this->getReviewerData($id, $reviewersDao);

            fputcsv($fp, $reviewersData);
        }

        fclose($fp);
    }
}
