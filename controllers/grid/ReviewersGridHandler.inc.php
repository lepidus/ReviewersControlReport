<?php

import('lib.pkp.classes.controllers.grid.GridHandler');
import('lib.pkp.classes.controllers.grid.DataObjectGridCellProvider');

class ReviewersGridHandler extends GridHandler
{
    private $_contextId;

    public function __construct()
    {
        parent::__construct();

        $this->addRoleAssignment(
            array(ROLE_ID_MANAGER),
            array(
                'fetchGrid',
                'fetchCategory',
                'fetchRow',
            )
        );
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.ContextAccessPolicy');
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        $context = $request->getContext();
        $this->_contextId = $context->getId();

        AppLocale::requireComponents(
            LOCALE_COMPONENT_PKP_USER,
            LOCALE_COMPONENT_PKP_MANAGER,
            LOCALE_COMPONENT_APP_MANAGER,
            LOCALE_COMPONENT_PKP_SUBMISSION
        );

        $this->setTitle('grid.roles.currentRoles');

        // import('plugins.generic.tutorialExample.controllers.grid.ReviewersGridCellProvider');
        // $cellProvider = new ReviewersGridCellProvider();
        $cellProvider = new DataObjectGridCellProvider();

        // $columnsInfo = [
        //     1 => ['id' => 'email', 'title' => 'plugins.generic.tutorialExample.field.email', 'template' => null],
        //     2 => ['id' => 'fullName', 'title' => 'plugins.generic.tutorialExample.field.fullName', 'template' => null],
        //     3 => ['id' => 'affiliation', 'title' => 'plugins.generic.tutorialExample.field.affiliation', 'template' => null],
        //     4 => ['id' => 'interests', 'title' => 'plugins.generic.tutorialExample.field.interests', 'template' => null],
        //     5 => ['id' => 'score', 'title' => 'plugins.generic.tutorialExample.field.qualityAverage', 'template' => null],
        //     6 => ['id' => 'totalReviews', 'title' => 'plugins.generic.tutorialExample.field.reviewedSubmissionsTotal', 'template' => null],
        //     7 => ['id' => 'reviews', 'title' => 'plugins.generic.tutorialExample.field.reviewedSubmissionsTitleAndCompletedDate', 'template' => null],
        //     8 => ['id' => 'edit', 'title' => 'grid.user.edit', 'template' => null],
        // ];

        // foreach ($columnsInfo as $columnInfo) {
        //     $this->addColumn(
        //         new GridColumn(
        //             $columnInfo['id'],
        //             $columnInfo['title'],
        //             null,
        //             $columnInfo['template'],
        //             $cellProvider
        //         )
        //     );
        // }

        $this->addColumn(
            new GridColumn(
                'name',
                'users.fullName',
                null,
                null,
                $cellProvider
            )
        );
    }

    protected function loadData($request, $filter)
    {
        $contextId = $this->_getContextId();
        // $userGroupDao = DAORegistry::getDAO('UserGroupDAO'); /* @var $userGroupDao UserGroupDAO */

        // $roleIdFilter = null;
        // $stageIdFilter = null;

        // if (!is_array($filter)) {
        //     $filter = array();
        // }

        // if (isset($filter['selectedRoleId'])) {
        //     $roleIdFilter = $filter['selectedRoleId'];
        // }

        // if (isset($filter['selectedStageId'])) {
        //     $stageIdFilter = $filter['selectedStageId'];
        // }



        // if ($stageIdFilter && $stageIdFilter != 0) {
        //     return $userGroupDao->getUserGroupsByStage($contextId, $stageIdFilter, $roleIdFilter, $rangeInfo);
        // } elseif ($roleIdFilter && $roleIdFilter != 0) {
        //     return $userGroupDao->getByRoleId($contextId, $roleIdFilter, false, $rangeInfo);
        // } else {
        //     return $userGroupDao->getByContextId($contextId, $rangeInfo);
        // }

        $rangeInfo = $this->getGridRangeInfo($request, $this->getId());

        // import('plugins.generic.tutorialExample.classes.ReviewersControlReport');
        // $reviewersControlReport = new ReviewersControlReport($contextId, $rangeInfo);
        // return $reviewersControlReport->assembleReport();

        $roleDao = DAORegistry::getDAO('RoleDAO');
        $reviewers = $roleDao->getUsersByRoleId(ROLE_ID_REVIEWER, $contextId);
        error_log(print_r($reviewers, true));
        return $reviewers;
    }


    protected function getRowInstance()
    {
        import('plugins.generic.tutorialExample.controllers.grid.ReviewersGridRow');
        return new ReviewersGridRow();
    }

    public function initFeatures($request, $args)
    {
        import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
        return array(new PagingFeature());
    }


    private function _getContextId()
    {
        return $this->_contextId;
    }
}
