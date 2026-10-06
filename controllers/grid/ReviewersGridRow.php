<?php

namespace APP\plugins\generic\reviewersControlReport\controllers\grid;

use PKP\controllers\grid\GridRow;
use PKP\core\PKPApplication;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RedirectAction;
use PKP\plugins\PluginRegistry;

class ReviewersGridRow extends GridRow
{
    private bool $canEditUsers;

    public function __construct(bool $canEditUsers = false)
    {
        parent::__construct();
        $this->canEditUsers = $canEditUsers;
    }

    /**
     * The user page sits under the journal settings, so the row offers the
     * action only to those who may open them.
     */
    public function canEditUsers(): bool
    {
        return $this->canEditUsers;
    }

    public function initialize($request, $template = null)
    {
        parent::initialize($request, $this->getPlugin()->getTemplateResource('gridRow.tpl'));

        if ($this->canEditUsers) {
            $this->addAction($this->getEditUserAction($request));
        }
    }

    protected function getEditUserAction($request): LinkAction
    {
        $editUserUrl = $request->getDispatcher()->url(
            $request,
            PKPApplication::ROUTE_PAGE,
            null,
            'management',
            'settings',
            ['user', $this->getId()]
        );

        return new LinkAction(
            'edit',
            new RedirectAction($editUserUrl),
            __('grid.user.edit'),
            'edit'
        );
    }

    private function getPlugin()
    {
        return PluginRegistry::getPlugin('generic', 'ReviewersControlReportPlugin');
    }

    public function getReviews()
    {
        return $this->getData()->getCompletedReviews();
    }
}
