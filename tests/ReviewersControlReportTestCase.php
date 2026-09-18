<?php

use APP\core\Application;
use APP\core\PageRouter;
use PKP\core\Registry;
use PKP\facades\Locale;
use PKP\tests\PKPTestCase;

require_once __DIR__ . '/RCRTestFixture.php';

abstract class ReviewersControlReportTestCase extends PKPTestCase
{
    protected RCRTestFixture $fixture;
    private $routerBackup;
    private $localeBackup;

    protected function setUp(): void
    {
        parent::setUp();

        // Unit tests do not route a request through a journal, so the locale
        // service has no context from which to select its primary locale.
        $this->fixture = new RCRTestFixture();
        $this->localeBackup = $this->readLocale();
        $this->writeLocale('en');

        // Nothing routes the request either, and the reports ask the request
        // for its context. Each test gets its own router, and gives it back.
        $request = Application::get()->getRequest();
        $this->routerBackup = $request->getRouter();
        $request->setRouter(new PageRouter());
    }

    protected function tearDown(): void
    {
        if (is_null($this->routerBackup)) {
            // The request had no router of its own: drop the one this test
            // installed instead of leaving it for the next test to find.
            Registry::delete('request');
        } else {
            Application::get()->getRequest()->setRouter($this->routerBackup);
        }
        $this->writeLocale($this->localeBackup);

        parent::tearDown();
    }

    protected function useLocale(string $locale): void
    {
        $this->writeLocale($locale);
    }

    private function readLocale(): string
    {
        $property = new ReflectionProperty(Locale::getFacadeRoot(), 'locale');
        $property->setAccessible(true);

        return (string) $property->getValue(Locale::getFacadeRoot());
    }

    private function writeLocale(string $locale): void
    {
        $property = new ReflectionProperty(Locale::getFacadeRoot(), 'locale');
        $property->setAccessible(true);
        $property->setValue(Locale::getFacadeRoot(), $locale);
    }
}
