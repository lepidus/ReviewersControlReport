<?php

import('lib.pkp.classes.plugins.GenericPlugin');

class ReviewersControlReportPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            PluginRegistry::register('reports', $this->getReportPlugin(), $this->getPluginPath());
            HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
        }

        return $success;
    }

    public function getReportPlugin()
    {
        $this->import('ReviewersControlReportReportPlugin');
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
        if ($component == 'plugins.generic.reviewersControlReport.controllers.grid.ReviewersGridHandler') {
            return true;
        }
        return false;
    }
}
