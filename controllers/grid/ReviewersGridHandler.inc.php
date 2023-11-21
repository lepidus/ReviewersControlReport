<?php

import('lib.pkp.classes.controllers.grid.GridHandler');
import('plugins.generic.reviewersControlReport.controllers.grid.ReviewersGridCellProvider');
import('plugins.generic.reviewersControlReport.classes.ReviewersControlReportDAO');

class ReviewersGridHandler extends GridHandler
{
    private $contextId;

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
        $this->contextId = $context->getId();

        AppLocale::requireComponents(
            LOCALE_COMPONENT_PKP_USER,
            LOCALE_COMPONENT_PKP_MANAGER,
            LOCALE_COMPONENT_APP_MANAGER,
            LOCALE_COMPONENT_PKP_SUBMISSION
        );

        $this->setTitle('plugins.reports.reviewersControlReport.displayName');

        $cellProvider = new ReviewersGridCellProvider();

        $columnsInfo = [
            1 => ['id' => 'email', 'title' => 'plugins.reports.reviewersControlReport.field.email', 'template' => null],
            2 => ['id' => 'fullName', 'title' => 'plugins.reports.reviewersControlReport.field.fullName', 'template' => null],
            3 => ['id' => 'affiliation', 'title' => 'plugins.reports.reviewersControlReport.field.affiliation', 'template' => null],
            4 => ['id' => 'interests', 'title' => 'plugins.reports.reviewersControlReport.field.interests', 'template' => null],
            5 => ['id' => 'score', 'title' => 'plugins.reports.reviewersControlReport.field.qualityAverage', 'template' => null],
            6 => ['id' => 'totalReviews', 'title' => 'plugins.reports.reviewersControlReport.field.reviewedSubmissionsTotal', 'template' => null],
            7 => ['id' => 'reviews', 'title' => 'plugins.reports.reviewersControlReport.field.reviewedSubmissionsTitleAndCompletedDate', 'template' => null],
        ];

        foreach ($columnsInfo as $columnInfo) {
            $this->addColumn(
                new GridColumn(
                    $columnInfo['id'],
                    $columnInfo['title'],
                    null,
                    $columnInfo['template'],
                    $cellProvider
                )
            );
        }
    }

    protected function loadData($request, $filter)
    {
        $contextId = $this->getContextId();
        $rangeInfo = $this->getGridRangeInfo($request, $this->getId());

        $reviewersControlReportDAO = new ReviewersControlReportDAO();
        $reviewers = $reviewersControlReportDAO->getReviewers($contextId, null, null, null, $rangeInfo);
        return $reviewers;
    }

    protected function getRowInstance()
    {
        import('plugins.generic.reviewersControlReport.controllers.grid.ReviewersGridRow');
        return new ReviewersGridRow();
    }

    public function initFeatures($request, $args)
    {
        import('lib.pkp.classes.controllers.grid.feature.PagingFeature');
        return array(new PagingFeature());
    }

    private function getContextId()
    {
        return $this->contextId;
    }
}
