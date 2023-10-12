<?php

/**
 * @file plugins/reports/reviewersControlReport/ReviewersControlReportPlugin.inc.php
 *
 * Copyright (c) 2019-2023 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ReviewersControlReportPlugin
 * @ingroup plugin_reports_reviewersControlReport
 *
 * @brief reviewersControlReport plugin class
 */

import('lib.pkp.classes.plugins.ReportPlugin');

class ReviewersControlReportPlugin extends ReportPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && Config::getVar('general', 'installed')) {
        }
        return $success;
    }

    public function getName()
    {
        return 'ReviewersControlReport';
    }

    public function getDisplayName()
    {
        return __('plugins.reports.reviewersControlReport.displayName');
    }

    public function getDescription()
    {
        return __('plugins.reports.reviewersControlReport.description');
    }

    public function display($args, $request)
    {
        return parent::display($args, $request);
    }
}
