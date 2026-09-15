<?php

import('lib.pkp.classes.controllers.grid.GridCategoryRow');
import('lib.pkp.classes.linkAction.request.AjaxModal');

class ReviewersGridRow extends GridRow
{
    private $canEditUsers;

    public function __construct(bool $canEditUsers = false)
    {
        parent::__construct();
        $this->canEditUsers = $canEditUsers;
    }

    public function initialize($request, $template = null)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'ReviewersControlReportPlugin');
        parent::initialize($request, $plugin->getTemplateResource('gridRow.tpl'));

        if (!$this->canEditUsers) {
            return;
        }

        $rowId = $this->getId();
        $dispatcher = $request->getDispatcher();

        $this->addAction(new LinkAction(
            'edit',
            new AjaxModal(
                $dispatcher->url(
                    $request,
                    ROUTE_COMPONENT,
                    null,
                    'grid.settings.user.UserGridHandler',
                    'editUser',
                    null,
                    ['rowId' => $rowId]
                ),
                __('grid.user.edit'),
                'modal_edit',
                true
            ),
            __('grid.user.edit'),
            'edit'
        ));
    }

    public function getReviews()
    {
        return $this->getData()->getReviewedSubmissionsTitleAndDate();
    }
}
