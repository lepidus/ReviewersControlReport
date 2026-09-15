<?php

namespace APP\plugins\generic\reviewersControlReport;

use APP\plugins\generic\reviewersControlReport\controllers\grid\ReviewersGridHandler;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\plugins\PluginRegistry;

class ReviewersControlReportPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if ($success && $this->getEnabled($mainContextId)) {
            PluginRegistry::register(
                'reports',
                $this->getReportPlugin(),
                $this->getPluginPath(),
                $mainContextId
            );
            Hook::add('LoadComponentHandler', [$this, 'setupGridHandler']);
        }

        return $success;
    }

    public function getReportPlugin()
    {
        return new ReviewersControlReportReportPlugin();
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

    public function setupGridHandler($hookName, $params)
    {
        $component = &$params[0];
        $componentInstance = &$params[2];
        if ($component == 'plugins.generic.reviewersControlReport.controllers.grid.ReviewersGridHandler') {
            $componentInstance = new ReviewersGridHandler($this);
            return true;
        }
        return false;
    }
}
