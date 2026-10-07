<?php

use APP\core\Application;
use APP\core\PageRouter;
use APP\facades\Repo;
use APP\plugins\generic\reviewersControlReport\controllers\grid\ReviewersGridHandler;
use APP\plugins\generic\reviewersControlReport\ReviewersControlReportPlugin;
use Illuminate\Support\Facades\DB;
use PKP\core\Dispatcher;
use PKP\core\Registry;
use PKP\security\authorization\UserRolesRequiredPolicy;
use PKP\security\Role;

require_once __DIR__ . '/../ReviewersControlReportTestCase.php';
require_once __DIR__ . '/../RCRReportFixtures.php';

class ReviewersGridAuthorizationTest extends ReviewersControlReportTestCase
{
    use RCRReportFixtures;

    private $contextId;
    private $contextPath;
    private $serverBackup;

    protected function getMockedRegistryKeys(): array
    {
        return [...parent::getMockedRegistryKeys(), 'request', 'user'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = $_SERVER;
        DB::beginTransaction();
        $this->contextPath = 'rcr' . uniqid();
        $this->contextId = $this->createContext($this->contextPath);
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        $_SERVER = $this->serverBackup;
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
        $this->assertTrue($this->mayEditUsersAs(Role::ROLE_ID_MANAGER));
    }

    public function testSiteAdministratorsMayEditTheListedReviewers()
    {
        $this->assertTrue($this->mayEditUsersAs(Role::ROLE_ID_SITE_ADMIN));
    }

    public function testSectionEditorsMayNotEditTheListedReviewers()
    {
        $this->assertFalse($this->mayEditUsersAs(Role::ROLE_ID_SUB_EDITOR));
    }

    private function mayEditUsersAs(int $roleId): bool
    {
        $handler = $this->createGridHandler();
        $this->authorizeHandler($handler, $roleId, 'fetchGrid');

        return $handler->canCurrentUserEditUsers();
    }

    private function authorizeAs(int $roleId, string $operation): bool
    {
        return $this->authorizeHandler($this->createGridHandler(), $roleId, $operation);
    }

    private function createGridHandler(): ReviewersGridHandler
    {
        return new ReviewersGridHandler($this->createMock(ReviewersControlReportPlugin::class));
    }

    private function authorizeHandler(ReviewersGridHandler $handler, int $roleId, string $operation): bool
    {
        $userId = $this->createUserWithRole($roleId);
        $request = $this->requestFor($this->contextPath . '/reviewers-grid/' . $operation);
        $user = Repo::user()->get($userId);
        Registry::set('user', $user);
        $request->getRouter()->setHandler($handler);
        // Tests run with the session disabled, and the core only loads the
        // roles of the user into the authorized context when it is enabled.
        $handler->addPolicy(new UserRolesRequiredPolicy($request), true);
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

    /**
     * PKPTestCase::mockRequest() would also start a session, which PHPUnit
     * cannot do once it has written its own output.
     */
    private function requestFor(string $path)
    {
        Registry::delete('request');
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['PATH_INFO'] = $path;
        $application = Application::get();
        $request = $application->getRequest();
        $router = new PageRouter();
        $router->setApplication($application);
        $dispatcher = new Dispatcher();
        $dispatcher->setApplication($application);
        $router->setDispatcher($dispatcher);
        $request->setRouter($router);

        return $request;
    }

    private function createUserWithRole(int $roleId): int
    {
        $userId = $this->createUser(['userName' => 'rcr' . uniqid()]);
        $this->giveUserTheRole($userId, $roleId, $this->contextId);

        return $userId;
    }
}
