<?php

import('lib.pkp.tests.PKPTestCase');
import('lib.pkp.classes.core.PKPRequest');
require_once dirname(__DIR__, 2) . '/ReviewersControlReportReportPlugin.inc.php';

class ReviewersControlReportReportPluginTest extends PKPTestCase
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
