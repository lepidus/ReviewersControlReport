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
import('plugins.generic.reviewersControlReport.classes.ReviewersControlReportDAO');

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
        $requestHandler = new PKPRequest();
        $form = new ReviewersControlReportForm();

        if ($requestHandler->isPost($request)) {
            $context = $request->getContext();
            $dao = new ReviewersControlReportDAO();
            $reviewersId = $dao->getReviewersIds($context->getId());
            $form->generateReport($reviewersId);
            return;
        }
        $dispatcher = $request->getDispatcher();
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
        $templateManager->display($this->getTemplateResource('index_component.tpl'));
    }
}
