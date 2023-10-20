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
import('plugins.reports.reviewersControlReport.classes.ReviewersControlReport');

class ReviewersControlReportPlugin extends ReportPlugin
{
    public function register($category, $path, $mainContextId = null): ?bool
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && Config::getVar('general', 'installed')) {
            $this->addLocaleData();
            return true;
        }
        return $success;
    }

    public function getName(): string
    {
        return 'ReviewersControlReportPlugin';
    }

    public function getDisplayName(): string
    {
        return __('plugins.reports.reviewersControlReport.displayName');
    }

    public function getDescription(): string
    {
        return __('plugins.reports.reviewersControlReport.description');
    }

    public function display($args, $request): void
    {
        $templateManager = TemplateManager::getManager();
        $templateManager->assign([
            'breadcrumbs' => [
                [
                    'id' => 'reports',
                    'name' => __('manager.statistics.reports'),
                    'url' => $request->getRouter()->url($request, null, 'stats', 'reports'),
                ],
                [
                    'id' => 'reviewersControlReport',
                    'name' => __('plugins.reports.reviewersControlReport.displayName')
                ],
            ],
            'pageTitle', __('plugins.reports.reviewersControlReport.displayName')
        ]);
        $templateManager->assign('report', $this->getReport($request));

        $templateManager->display($this->getTemplateResource('index.tpl'));
    }

    public function getReport($request): array
    {
        $contextId = $request->getContext() ? $request->getContext()->getId() : CONTEXT_SITE;
        $reviewersControlReport = new ReviewersControlReport($contextId);
        return $reviewersControlReport->assembleReport();
    }
}
