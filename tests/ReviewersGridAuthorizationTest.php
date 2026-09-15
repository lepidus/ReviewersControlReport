<?php

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.pages.stats.PKPStatsHandler');
import('plugins.generic.reviewersControlReport.controllers.grid.ReviewersGridHandler');

class ReviewersGridAuthorizationTest extends PKPTestCase
{
    public function testGridRolesMatchTheCoreReportsPageRoles()
    {
        $coreRoles = $this->getRolesForOperation(new PKPStatsHandler(), 'reports');
        $gridHandler = new ReviewersGridHandler();

        foreach (['fetchGrid', 'fetchCategory', 'fetchRow'] as $operation) {
            $this->assertSame($coreRoles, $this->getRolesForOperation($gridHandler, $operation));
        }

        $this->assertSame([ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR], $coreRoles);
        $this->assertNotContains(ROLE_ID_REVIEWER, $coreRoles);
    }

    public function testOnlyManagersAndSiteAdministratorsCanEditReviewers()
    {
        $this->assertTrue(ReviewersGridHandler::canRolesEditUsers([ROLE_ID_MANAGER]));
        $this->assertTrue(ReviewersGridHandler::canRolesEditUsers([ROLE_ID_SITE_ADMIN]));
        $this->assertTrue(ReviewersGridHandler::canRolesEditUsers([ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER]));
        $this->assertFalse(ReviewersGridHandler::canRolesEditUsers([ROLE_ID_SUB_EDITOR]));
        $this->assertFalse(ReviewersGridHandler::canRolesEditUsers([]));
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
