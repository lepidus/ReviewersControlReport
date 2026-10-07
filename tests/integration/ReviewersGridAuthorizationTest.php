<?php

import('lib.pkp.tests.DatabaseTestCase');
import('classes.core.PageRouter');
import('lib.pkp.classes.core.Dispatcher');
import('lib.pkp.classes.security.authorization.UserRolesRequiredPolicy');
import('plugins.generic.reviewersControlReport.controllers.grid.ReviewersGridHandler');

require_once __DIR__ . '/../RCRReportFixtures.php';

class ReviewersGridAuthorizationTest extends DatabaseTestCase
{
    use RCRReportFixtures;

    private $contextId;
    private $contextPath = 'rcr-grid-access';
    private $serverBackup;

    protected function getAffectedTables()
    {
        return ['journals', 'journal_settings', 'users', 'user_settings',
            'user_groups', 'user_group_settings', 'user_user_groups'];
    }

    protected function getMockedRegistryKeys()
    {
        return ['request', 'user'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->serverBackup = $_SERVER;
        $this->contextId = $this->createContext($this->contextPath);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        parent::tearDown();
    }

    public function testManagersReachTheGrid()
    {
        $this->assertTrue($this->authorizeAs(ROLE_ID_MANAGER, 'fetchGrid'));
    }

    public function testSectionEditorsReachTheGrid()
    {
        $this->assertTrue($this->authorizeAs(ROLE_ID_SUB_EDITOR, 'fetchGrid'));
    }

    public function testReviewersDoNotReachTheGrid()
    {
        $this->assertFalse($this->authorizeAs(ROLE_ID_REVIEWER, 'fetchGrid'));
    }

    public function testAuthorsDoNotReachTheGrid()
    {
        $this->assertFalse($this->authorizeAs(ROLE_ID_AUTHOR, 'fetchGrid'));
    }

    public function testUndeclaredOperationsAreDeniedToManagers()
    {
        $this->assertFalse($this->authorizeAs(ROLE_ID_MANAGER, 'enable'));
    }

    public function testManagersMayEditTheListedReviewers()
    {
        $this->assertTrue($this->mayEditUsersAs(ROLE_ID_MANAGER));
    }

    public function testSiteAdministratorsMayEditTheListedReviewers()
    {
        $this->assertTrue($this->mayEditUsersAs(ROLE_ID_SITE_ADMIN));
    }

    public function testSectionEditorsMayNotEditTheListedReviewers()
    {
        $this->assertFalse($this->mayEditUsersAs(ROLE_ID_SUB_EDITOR));
    }

    private function mayEditUsersAs(int $roleId): bool
    {
        $handler = new ReviewersGridHandler();
        $this->authorizeHandler($handler, $roleId, 'fetchGrid');

        return $handler->canCurrentUserEditUsers();
    }

    private function authorizeAs(int $roleId, string $operation): bool
    {
        return $this->authorizeHandler(new ReviewersGridHandler(), $roleId, $operation);
    }

    private function authorizeHandler(ReviewersGridHandler $handler, int $roleId, string $operation): bool
    {
        $userId = $this->createUserWithRole($roleId);
        $request = $this->requestFor($this->contextPath . '/reviewers-grid/' . $operation);
        $user = DAORegistry::getDAO('UserDAO')->getById($userId);
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
            (array) $handler->getAuthorizedContextObject(ASSOC_TYPE_USER_ROLES),
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
        $userId = $this->createUser(['userName' => 'walter.salles.' . $roleId]);
        $this->giveUserTheRole($userId, $roleId, $this->contextId);

        return $userId;
    }
}
