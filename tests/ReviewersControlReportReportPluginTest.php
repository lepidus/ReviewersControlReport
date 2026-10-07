<?php

use APP\plugins\generic\reviewersControlReport\ReviewersControlReportReportPlugin;
use PKP\core\PKPRequest;

use PKP\tests\PKPTestCase;

class ReviewersControlReportReportPluginTest extends PKPTestCase
{
    public function testStyleSheetUrlChangesWhenTheStyleSheetChanges()
    {
        $request = $this->createMock(PKPRequest::class);
        $request->method('getBaseUrl')->willReturn('https://example.test');
        $plugin = new ReviewersControlReportReportPlugin();
        $plugin->pluginPath = 'plugins/generic/reviewersControlReport';
        $styleSheetVersion = filemtime(dirname(__DIR__) . '/styles/reviewersControlReport.css');

        $this->assertSame(
            'https://example.test/plugins/generic/reviewersControlReport/styles/reviewersControlReport.css?v=' . $styleSheetVersion,
            $plugin->getStyleSheetUrl($request)
        );
    }
}
