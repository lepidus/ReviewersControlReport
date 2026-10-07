<?php

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.core.PKPRequest');
import('lib.pkp.classes.core.PKPComponentRouter');
import('classes.journal.Journal');
import('plugins.generic.reviewersControlReport.controllers.grid.ReviewersGridHandler');

class ReviewersGridHandlerTest extends PKPTestCase
{
    public function testGridHasNoTitleOfItsOwnSinceThePageAlreadyHeadsIt()
    {
        $context = $this->createMock(Journal::class);
        $context->method('getId')->willReturn(1);
        $request = $this->createMock(PKPRequest::class);
        $request->method('getContext')->willReturn($context);
        $request->method('getRouter')->willReturn($this->createMock(PKPComponentRouter::class));

        $gridHandler = new ReviewersGridHandler();
        $gridHandler->initialize($request);

        $this->assertEmpty($gridHandler->getTitle());
        $this->assertCount(6, $gridHandler->getColumns());
    }
}
