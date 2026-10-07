<?php

use APP\journal\Journal;
use APP\plugins\generic\reviewersControlReport\controllers\grid\ReviewersGridHandler;
use APP\plugins\generic\reviewersControlReport\ReviewersControlReportPlugin;
use PKP\core\PKPComponentRouter;
use PKP\core\PKPRequest;

require_once __DIR__ . '/../ReviewersControlReportTestCase.php';

class ReviewersGridHandlerTest extends ReviewersControlReportTestCase
{
    public function testGridHasNoTitleOfItsOwnSinceThePageAlreadyHeadsIt()
    {
        $context = $this->createMock(Journal::class);
        $context->method('getId')->willReturn(1);
        $request = $this->createMock(PKPRequest::class);
        $request->method('getContext')->willReturn($context);
        $request->method('getRouter')->willReturn($this->createMock(PKPComponentRouter::class));

        $gridHandler = new ReviewersGridHandler($this->createMock(ReviewersControlReportPlugin::class));
        $gridHandler->initialize($request);

        $this->assertEmpty($gridHandler->getTitle());
        $this->assertCount(6, $gridHandler->getColumns());
    }
}
