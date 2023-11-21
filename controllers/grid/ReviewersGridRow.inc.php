<?php

import('lib.pkp.classes.controllers.grid.GridCategoryRow');
import('lib.pkp.classes.linkAction.request.AjaxModal');

class ReviewersGridRow extends GridRow
{
    public function initialize($request, $template = null)
    {
        parent::initialize($request, $template);

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
            __('grid.action.edit'),
            'edit'
        ));

        $this->addAction(new LinkAction(
            'reviews',
            new AjaxModal(
                $request->getRouter()->url($request, null, null, 'reviews', null, $this->getRequestArgs()),
                __('plugins.reports.reviewersControlReport.reviews'),
                'modal_add_item'
            ),
            __('grid.action.addGalley'),
            'add_item'
        ));
    }
}
