<?php

namespace APP\plugins\generic\reviewersControlReport\controllers\grid;

use APP\plugins\generic\reviewersControlReport\classes\ReviewersControlReportDAO;
use APP\plugins\generic\reviewersControlReport\ReviewersControlReportPlugin;
use PKP\controllers\grid\feature\PagingFeature;
use PKP\controllers\grid\GridColumn;
use PKP\controllers\grid\GridHandler;
use PKP\i18n\PKPLocale;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\security\Role;

class ReviewersGridHandler extends GridHandler
{
    private $contextId;
    private $plugin;

    public function __construct(ReviewersControlReportPlugin $plugin)
    {
        parent::__construct();
        $this->plugin = $plugin;

        $this->addRoleAssignment(
            [Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR],
            array(
                'fetchGrid',
                'fetchCategory',
                'fetchRow',
            )
        );
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));

        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request, $args = null)
    {
        parent::initialize($request, $args);

        $context = $request->getContext();
        $this->contextId = $context->getId();

        PKPLocale::requireComponents(
            LOCALE_COMPONENT_PKP_USER,
            LOCALE_COMPONENT_PKP_MANAGER,
            LOCALE_COMPONENT_APP_MANAGER,
            LOCALE_COMPONENT_PKP_SUBMISSION
        );

        $this->setTitle('plugins.reports.reviewersControlReport.displayName');

        $cellProvider = new ReviewersGridCellProvider();

        $columnsInfo = [
            1 => ['id' => 'fullName', 'title' => 'plugins.reports.reviewersControlReport.field.fullName', 'template' => null],
            2 => ['id' => 'email', 'title' => 'plugins.reports.reviewersControlReport.field.email', 'template' => null],
            3 => ['id' => 'affiliation', 'title' => 'plugins.reports.reviewersControlReport.field.affiliation', 'template' => null],
            4 => ['id' => 'interests', 'title' => 'plugins.reports.reviewersControlReport.field.interests', 'template' => null],
            5 => ['id' => 'score', 'title' => 'plugins.reports.reviewersControlReport.field.qualityAverage', 'template' => null],
            6 => ['id' => 'totalReviews', 'title' => 'plugins.reports.reviewersControlReport.field.reviewedSubmissionsTotal', 'template' => null]
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
        $reviewers = $reviewersControlReportDAO->getReviewers($contextId, $rangeInfo);
        return $reviewers;
    }

    protected function getRowInstance()
    {
        return new ReviewersGridRow($this->plugin);
    }

    public function initFeatures($request, $args)
    {
        return [new PagingFeature()];
    }

    private function getContextId()
    {
        return $this->contextId;
    }
}
