<?php

use APP\plugins\generic\reviewersControlReport\controllers\grid\ReviewersGridRow;
use PKP\core\Dispatcher;
use PKP\core\PKPApplication;
use PKP\core\PKPRequest;
use PKP\linkAction\request\RedirectAction;

require_once __DIR__ . '/../ReviewersControlReportTestCase.php';

class ReviewersGridRowTest extends ReviewersControlReportTestCase
{
    public function testEditUserActionOpensTheUserPageOfTheJournalSettings()
    {
        $reviewerId = 7;
        $request = $this->createMock(PKPRequest::class);
        $dispatcher = $this->createMock(Dispatcher::class);
        $request->method('getDispatcher')->willReturn($dispatcher);
        $dispatcher->expects($this->once())
            ->method('url')
            ->with($request, PKPApplication::ROUTE_PAGE, null, 'management', 'settings', ['user', $reviewerId])
            ->willReturn('https://example.test/index.php/journal/management/settings/user/7');

        $row = new ReviewersGridRow(true);
        $row->setId($reviewerId);
        $method = new ReflectionMethod($row, 'getEditUserAction');
        $action = $method->invoke($row, $request);

        $actionRequest = $action->getActionRequest();
        $this->assertInstanceOf(RedirectAction::class, $actionRequest);
        $this->assertSame('https://example.test/index.php/journal/management/settings/user/7', $actionRequest->getUrl());
    }
}
