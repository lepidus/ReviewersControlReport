<?php

use APP\facades\Repo;
use APP\plugins\generic\reviewersControlReport\controllers\grid\ReviewersGridHandler;
use Illuminate\Support\Facades\DB;
use PKP\core\Registry;
use PKP\security\authorization\UserRolesRequiredPolicy;
use PKP\security\Role;

require_once __DIR__ . '/ReviewersControlReportTestCase.php';

class ReviewersGridAuthorizationTest extends ReviewersControlReportTestCase
{
    private $contextId = 1;

    protected function getMockedRegistryKeys(): array
    {
        return [...parent::getMockedRegistryKeys(), 'user'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function testManagersReachTheGrid()
    {
        $this->assertTrue($this->authorizeAs(Role::ROLE_ID_MANAGER, 'fetchGrid'));
    }

    public function testSectionEditorsReachTheGrid()
    {
        $this->assertTrue($this->authorizeAs(Role::ROLE_ID_SUB_EDITOR, 'fetchGrid'));
    }

    public function testReviewersDoNotReachTheGrid()
    {
        $this->assertFalse($this->authorizeAs(Role::ROLE_ID_REVIEWER, 'fetchGrid'));
    }

    public function testAuthorsDoNotReachTheGrid()
    {
        $this->assertFalse($this->authorizeAs(Role::ROLE_ID_AUTHOR, 'fetchGrid'));
    }

    public function testUndeclaredOperationsAreDeniedToManagers()
    {
        $this->assertFalse($this->authorizeAs(Role::ROLE_ID_MANAGER, 'enable'));
    }

    private function authorizeAs(int $roleId, string $operation): bool
    {
        $userId = $this->createUserWithRole($roleId);
        $request = $this->mockRequest('publicknowledge/reviewers-grid/' . $operation, $userId);
        $user = Repo::user()->get($userId);
        Registry::set('user', $user);
        $handler = new ReviewersGridHandler();
        $request->getRouter()->setHandler($handler);
        // Tests run with the session disabled, and the core only loads the
        // roles of the user into the authorized context when it is enabled.
        $userRolesPolicy = new UserRolesRequiredPolicy($request);
        $handler->addPolicy($userRolesPolicy, true);
        $args = [];

        return $handler->authorize($request, $args, $handler->getRoleAssignments());
    }

    private function createUserWithRole(int $roleId): int
    {
        $user = Repo::user()->newDataObject();
        $user->setData('givenName', ['en' => 'Walter']);
        $user->setData('familyName', ['en' => 'Salles']);
        $user->setData('email', 'walter.salles.' . $roleId . '@example.test');
        $user->setData('userName', 'walter.salles.' . $roleId);
        $user->setData('password', 'walter.salles');
        $user->setData('dateRegistered', '2026-01-01 00:00:00');
        $userId = Repo::user()->add($user);

        $userGroup = Repo::userGroup()->getByRoleIds([$roleId], $this->contextId)->first();
        $this->assertNotNull($userGroup, 'The test journal has no user group for role ' . $roleId);
        Repo::userGroup()->assignUserToGroup($userId, $userGroup->id);

        return $userId;
    }
}
