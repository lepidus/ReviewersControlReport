<?php

use PKP\tests\PKPTestCase;

class ReviewersControlReportLocaleTest extends PKPTestCase
{
    private const PLUGIN_KEY_PREFIX = 'plugins.reports.reviewersControlReport.';
    private const PLUGIN_LOCALES = ['en', 'es', 'pt_BR'];

    public function testEveryPluginKeyUsedByTheTemplatesIsTranslatedInEveryPluginLocale()
    {
        $pluginKeys = array_filter(
            $this->getKeysUsedByTemplates(),
            fn (string $key) => str_starts_with($key, self::PLUGIN_KEY_PREFIX)
        );

        foreach (self::PLUGIN_LOCALES as $locale) {
            $missingKeys = array_diff($pluginKeys, $this->getMessageIds(dirname(__DIR__) . "/locale/{$locale}"));
            $this->assertSame([], array_values($missingKeys), "Keys missing from the {$locale} locale");
        }
    }

    public function testEveryCoreKeyUsedByTheTemplatesExistsInTheInstalledApplication()
    {
        $coreKeys = array_filter(
            $this->getKeysUsedByTemplates(),
            fn (string $key) => !str_starts_with($key, self::PLUGIN_KEY_PREFIX)
        );
        $coreMessageIds = array_merge(
            $this->getMessageIds(BASE_SYS_DIR . '/lib/pkp/locale/en'),
            $this->getMessageIds(BASE_SYS_DIR . '/locale/en')
        );

        $missingKeys = array_diff($coreKeys, $coreMessageIds);
        $this->assertSame([], array_values($missingKeys));
    }

    private function getKeysUsedByTemplates(): array
    {
        $keys = [];
        foreach (glob(dirname(__DIR__) . '/templates/*.tpl') as $template) {
            preg_match_all('/\b(?:key|title|description|label)="([\w.]+)"/', file_get_contents($template), $matches);
            $keys = array_merge($keys, $matches[1]);
        }
        return array_values(array_unique($keys));
    }

    private function getMessageIds(string $localeDirectory): array
    {
        $messageIds = [];
        foreach (glob("{$localeDirectory}/*.po") as $file) {
            preg_match_all('/^msgid "(.+)"$/m', file_get_contents($file), $matches);
            $messageIds = array_merge($messageIds, $matches[1]);
        }
        return $messageIds;
    }
}
