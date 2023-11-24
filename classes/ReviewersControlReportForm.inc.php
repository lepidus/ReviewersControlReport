<?php

import('lib.pkp.classes.form.Form');
import('plugins.generic.reviewersControlReport.classes.ReviewersControlReportDAO');

class ReviewersControlReportForm extends Form
{
    public function execute($reviewersId)
    {
        header('content-type: text/comma-separated-values');
        header("content-disposition: attachment; filename=reviewers-$type-" . date('Ymd') . '.csv');

        $columns = array(
            __('plugins.reports.reviewersControlReport.field.fullName'),
        );

        $fp = fopen('php://output', 'wt');
        fputcsv($fp, $columns);

        foreach ($reviewersId as $id) {
            $reviewersData = [];
            $userDao = DAORegistry::getDAO('UserDAO');
            $user = $userDao->getById($id);

            $reviewersData[] = $user->getLocalizedGivenName() . $user->getLocalizedFamilyName();

            fputcsv($fp, $reviewersData);
        }

        fclose($fp);
    }
}
