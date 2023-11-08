<?php

trait ReviewerGridHandlerLinkAction
{
    public function getEditUserReviewerLinkAction()
    {
        $request = \Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();

        $editUserAction = new LinkAction(
            'edit',
            new AjaxModal(
                $dispatcher->url(
                    $request,
                    ROUTE_COMPONENT,
                    null,
                    'grid.settings.user.UserGridHandler',
                    'editUser',
                    null,
                    ['rowId' => $this->id]
                ),
                __('grid.user.edit'),
                'modal_edit',
                true
            ),
            __('grid.action.edit'),
            'edit'
        );

        return $editUserAction;
    }
}
