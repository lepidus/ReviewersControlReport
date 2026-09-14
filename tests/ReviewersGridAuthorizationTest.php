<?php

use APP\plugins\generic\reviewersControlReport\controllers\grid\ReviewersGridHandler;
use PKP\pages\stats\PKPStatsHandler;
use PKP\security\Role;

require_once __DIR__ . '/ReviewersControlReportTestCase.php';

class ReviewersGridAuthorizationTest extends ReviewersControlReportTestCase
{
    public function testGridRolesMatchTheCoreReportsPageRoles()
    {
        $coreRoles = $this->getRolesForOperation(new PKPStatsHandler(), 'reports');
        $gridHandler = new ReviewersGridHandler();

        foreach (['fetchGrid', 'fetchCategory', 'fetchRow'] as $operation) {
            $this->assertSame($coreRoles, $this->getRolesForOperation($gridHandler, $operation));
        }

        $this->assertSame(
            [Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR],
            $coreRoles
        );
        $this->assertNotContains(Role::ROLE_ID_REVIEWER, $coreRoles);
    }

    private function getRolesForOperation($handler, string $operation): array
    {
        $roles = [];
        foreach ($handler->getRoleAssignments() as $roleId => $operations) {
            if (in_array($operation, $operations, true)) {
                $roles[] = $roleId;
            }
        }
        sort($roles);
        return $roles;
    }
}
