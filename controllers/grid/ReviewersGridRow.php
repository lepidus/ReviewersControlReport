<?php

namespace APP\plugins\generic\reviewersControlReport\controllers\grid;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\reviewersControlReport\ReviewersControlReportPlugin;
use PKP\controllers\grid\GridRow;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\AjaxModal;
use PKP\security\Role;
use PKP\security\Validation;

class ReviewersGridRow extends GridRow
{
    private $plugin;

    public function __construct(ReviewersControlReportPlugin $plugin)
    {
        parent::__construct();
        $this->plugin = $plugin;
    }

    public function initialize($request, $template = null)
    {
        parent::initialize($request, $this->plugin->getTemplateResource('gridRow.tpl'));

        $rowId = $this->getId();
        $dispatcher = $request->getDispatcher();

        if (!$this->canEditUsers($request)) {
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

    private function canEditUsers($request): bool
    {
        if (Validation::isSiteAdmin()) {
            return true;
        }

        $user = $request->getUser();
        $context = $request->getContext();
        if (!$user || !$context) {
            return false;
        }

        foreach (Repo::userGroup()->userUserGroups($user->getId(), $context->getId()) as $userGroup) {
            if ($userGroup->getRoleId() === Role::ROLE_ID_MANAGER) {
                return true;
            }
        }

        return false;
    }

    public function getReviews()
    {
        return $this->getData()->getReviewedSubmissionsTitleAndDate();
    }
}
