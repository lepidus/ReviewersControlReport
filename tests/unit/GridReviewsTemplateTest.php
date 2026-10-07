<?php

use APP\core\Application;
use APP\core\PageRouter;
use APP\template\TemplateManager;
use PKP\core\Dispatcher;
use PKP\core\Registry;
use PKP\facades\Locale;

require_once __DIR__ . '/../ReviewersControlReportTestCase.php';

class GridReviewsTemplateTest extends ReviewersControlReportTestCase
{
    protected function getMockedRegistryKeys(): array
    {
        return ['request'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Locale::registerPath(dirname(__DIR__, 2) . '/locale');
    }

    public function testSubmissionTitleAndWorkflowUrlAreEscaped()
    {
        $html = $this->renderReviews([[
            'title' => '<img src=x onerror=alert(1)>',
            'url' => 'https://example.test/workflow?value=" onclick="alert(2)&other=1',
            'dateCompleted' => '2026-01-15',
        ]]);

        $this->assertStringContainsString(
            'href="https://example.test/workflow?value=&quot; onclick=&quot;alert(2)&amp;other=1"',
            $html
        );
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringNotContainsString('<img', $html);
    }

    public function testReviewsAreHeadedOnceByTheirColumnTitles()
    {
        $html = $this->renderReviews([
            ['title' => 'First', 'url' => 'https://example.test/1', 'dateCompleted' => '2026-01-15'],
            ['title' => 'Second', 'url' => 'https://example.test/2', 'dateCompleted' => '2026-02-20'],
        ]);

        $this->assertSame(1, substr_count($html, 'reviewersControlReport__reviewsHeader'));
        $this->assertSame(2, substr_count($html, 'reviewersControlReport__review '));
        $this->assertStringContainsString('>Submission Title</th>', $html);
        $this->assertStringContainsString('>Date Completed</th>', $html);
    }

    public function testDateCompletedIsShownAsDayMonthYearWithoutRepeatingItsLabel()
    {
        $html = $this->renderReviews([
            ['title' => 'First', 'url' => 'https://example.test/1', 'dateCompleted' => '2026-01-15'],
        ]);

        $this->assertStringContainsString('<td colspan="2">15/01/2026</td>', $html);
        $this->assertStringNotContainsString('Completed:', $html);
    }

    private function renderReviews(array $reviews): string
    {
        $templateManager = TemplateManager::getManager($this->requestWithoutSession());
        $templateManager->assign([
            'rowId' => 'component-grid-row-1',
            'reviews' => $reviews,
            'columnsCount' => 6,
            'dateFormatShort' => 'Y-m-d',
        ]);
        return $templateManager->fetch('file:' . dirname(__DIR__, 2) . '/templates/gridReviews.tpl');
    }

    private function requestWithoutSession()
    {
        Registry::delete('request');
        $_SERVER['PATH_INFO'] = 'index/test-page/test-op';
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
}
