<?php

use APP\plugins\generic\reviewersControlReport\ReviewersControlReportReportPlugin;
use PKP\core\PKPRequest;

require_once __DIR__ . '/../ReviewersControlReportTestCase.php';

class ReviewersControlReportReportPluginTest extends ReviewersControlReportTestCase
{
    public function testStyleSheetUrlChangesWhenTheStyleSheetChanges()
    {
        $request = $this->createMock(PKPRequest::class);
        $request->method('getBaseUrl')->willReturn('https://example.test');
        $plugin = new ReviewersControlReportReportPlugin();
        $plugin->pluginPath = 'plugins/generic/reviewersControlReport';
        $styleSheetVersion = filemtime(dirname(__DIR__, 2) . '/styles/reviewersControlReport.css');

        $this->assertSame(
            'https://example.test/plugins/generic/reviewersControlReport/styles/reviewersControlReport.css?v=' . $styleSheetVersion,
            $plugin->getStyleSheetUrl($request)
        );
    }
}
