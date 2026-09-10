<?php

/**
 * @file plugins/reports/reviewersControlReport/ReviewersControlReportReportPlugin.inc.php
 *
 * Copyright (c) 2019-2023 Lepidus Tecnologia
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class ReviewersControlReportReportPlugin
 * @ingroup plugin_reports_reviewersControlReport
 *
 * @brief reviewersControlReport plugin class
 */

import('lib.pkp.classes.plugins.ReportPlugin');
import('plugins.generic.reviewersControlReport.classes.ReviewersControlReportForm');

class ReviewersControlReportReportPlugin extends ReportPlugin
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
        return 'ReviewersControlReportReportPlugin';
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
        AppLocale::requireComponents(
            LOCALE_COMPONENT_PKP_GRID
        );

        $form = new ReviewersControlReportForm($this);

        if ($request->isPost()) {
            $form->readInputData();
            if ($form->validate()) {
                $form->generateReport($request);
                return;
            }
        } else {
            $form->initData();
        }

        $templateManager = TemplateManager::getManager($request);
        $templateManager->addStyleSheet(
            'reviewersControlReport',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/styles/reviewersControlReport.css',
            ['contexts' => 'backend']
        );
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
            'pageTitle' => __('plugins.reports.reviewersControlReport.displayName'),
            'reportTypes' => [
                ReviewersControlReportForm::REPORT_TYPE_REVIEWERS => __('plugins.reports.reviewersControlReport.reportType.reviewers'),
                ReviewersControlReportForm::REPORT_TYPE_REVIEWS => __('plugins.reports.reviewersControlReport.reportType.reviews'),
            ],
        ]);
        $form->display($request);
    }
}
