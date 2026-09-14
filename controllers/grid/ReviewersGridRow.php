<?php

namespace APP\plugins\generic\reviewersControlReport\controllers\grid;

use PKP\controllers\grid\GridRow;
use PKP\core\PKPApplication;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\plugins\PluginRegistry;

class ReviewersGridRow extends GridRow
{
    private bool $canEditUsers;

    public function __construct(bool $canEditUsers = false)
    {
        parent::__construct();
        $this->canEditUsers = $canEditUsers;
    }

    public function initialize($request, $template = null)
    {
        $plugin = PluginRegistry::getPlugin('generic', 'ReviewersControlReportPlugin');
        parent::initialize($request, $plugin->getTemplateResource('gridRow.tpl'));

        $rowId = $this->getId();
        $dispatcher = $request->getDispatcher();

        if (!$this->canEditUsers) {
            return;
        }

        $this->addAction(new LinkAction(
            'edit',
            new AjaxModal(
                $dispatcher->url(
                    $request,
                    PKPApplication::ROUTE_COMPONENT,
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
