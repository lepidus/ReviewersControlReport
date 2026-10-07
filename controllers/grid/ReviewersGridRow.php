<?php

namespace APP\plugins\generic\reviewersControlReport\controllers\grid;

use APP\core\Application;
use APP\plugins\generic\reviewersControlReport\ReviewersControlReportPlugin;
use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;

class ReviewersGridRow extends GridRow
{
    private $plugin;
    private $canEditUsers;

    public function __construct(ReviewersControlReportPlugin $plugin, bool $canEditUsers = false)
    {
        parent::__construct();
        $this->plugin = $plugin;
        $this->canEditUsers = $canEditUsers;
    }

    public function canEditUsers(): bool
    {
        return $this->canEditUsers;
    }

    public function initialize($request, $template = null)
    {
        parent::initialize($request, $this->plugin->getTemplateResource('gridRow.tpl'));

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
                    Application::ROUTE_COMPONENT,
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

    public function getReviewsTemplate(): string
    {
        return $this->plugin->getTemplateResource('gridReviews.tpl');
    }

    public function getReviews()
    {
        return $this->getData()->getCompletedReviews();
    }
}
