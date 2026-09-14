<?php

use PKP\facades\Locale;
use PKP\tests\PKPTestCase;

abstract class ReviewersControlReportTestCase extends PKPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Unit tests do not route a request through a journal, so the locale
        // service has no context from which to select its primary locale.
        $property = new ReflectionProperty(Locale::getFacadeRoot(), 'locale');
        $property->setAccessible(true);
        $property->setValue(Locale::getFacadeRoot(), 'en');
    }
}
