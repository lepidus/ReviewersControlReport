<?php

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\reviewersControlReport\controllers\grid\ReviewersGridHandler;
use APP\plugins\generic\reviewersControlReport\controllers\grid\ReviewersGridRow;
use Illuminate\Support\Facades\DB;
use PKP\core\Registry;
use PKP\security\authorization\UserRolesRequiredPolicy;
use PKP\security\Role;
use PKP\userGroup\UserGroup;

require_once __DIR__ . '/../ReviewersControlReportTestCase.php';

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

    public function testManagersMayEditTheListedReviewers()
    {
        $this->assertTrue($this->rowOfGridAs(Role::ROLE_ID_MANAGER)->canEditUsers());
    }

    public function testSiteAdministratorsMayEditTheListedReviewers()
    {
        $this->assertTrue($this->rowOfGridAs(Role::ROLE_ID_SITE_ADMIN)->canEditUsers());
    }

    public function testSectionEditorsMayNotEditTheListedReviewers()
    {
        $this->assertFalse($this->rowOfGridAs(Role::ROLE_ID_SUB_EDITOR)->canEditUsers());
    }

    private function rowOfGridAs(int $roleId): ReviewersGridRow
    {
        $handler = new ReviewersGridHandler();
        $this->authorizeHandler($handler, $roleId, 'fetchGrid');

        $getRowInstance = new ReflectionMethod($handler, 'getRowInstance');
        $getRowInstance->setAccessible(true);

        return $getRowInstance->invoke($handler);
    }

    private function authorizeAs(int $roleId, string $operation): bool
    {
        return $this->authorizeHandler(new ReviewersGridHandler(), $roleId, $operation);
    }

    private function authorizeHandler(ReviewersGridHandler $handler, int $roleId, string $operation): bool
    {
        $userId = $this->createUserWithRole($roleId);
        $request = $this->mockRequest('publicknowledge/reviewers-grid/' . $operation, $userId);
        $user = Repo::user()->get($userId);
        Registry::set('user', $user);
        $request->getRouter()->setHandler($handler);
        // Tests run with the session disabled, and the core only loads the
        // roles of the user into the authorized context when it is enabled.
        $userRolesPolicy = new UserRolesRequiredPolicy($request);
        $handler->addPolicy($userRolesPolicy, true);
        $args = [];

        $decision = $handler->authorize($request, $args, $handler->getRoleAssignments());

        // A denial means nothing when the roles of the user never reached the
        // authorized context: every role would be denied, for the wrong reason.
        $this->assertContains(
            $roleId,
            (array) $handler->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES),
            'The role of the user did not reach the authorized context'
        );

        return $decision;
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

        // Site administrators hold their role on the site, which has no
        // context id of its own, so the group is looked up by role alone.
        $userGroup = $roleId === Role::ROLE_ID_SITE_ADMIN
            ? UserGroup::withRoleIds([$roleId])->first()
            : Repo::userGroup()->getByRoleIds([$roleId], $this->contextId)->first();
        $this->assertNotNull($userGroup, 'The test database has no user group for role ' . $roleId);
        Repo::userGroup()->assignUserToGroup($userId, $userGroup->id);

        return $userId;
    }
}
