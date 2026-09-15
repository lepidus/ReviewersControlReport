<?php

/**
 * HTTP contract against the disposable CI fixture, never a production journal.
 * See tests/HTTP_SECURITY.md for the required seeded actors and markers.
 */

$baseUrl = getenv('RCR_TEST_URL');
if (!$baseUrl || getenv('RCR_TEST_FIXTURE') !== 'disposable') {
    fwrite(STDERR, "Set RCR_TEST_URL and RCR_TEST_FIXTURE=disposable for the prepared CI fixture.\n");
    exit(2);
}
$baseUrl = rtrim($baseUrl, '/') . '/';
$failures = 0;
$assertions = 0;

function check(bool $condition, string $message): void
{
    global $failures, $assertions;
    $assertions++;
    if (!$condition) {
        $failures++;
        fwrite(STDERR, "FAIL: " . $message . "\n");
    }
}

function requestPage(string $path, string $cookie, ?array $data = null): array
{
    global $baseUrl;
    $curl = curl_init($baseUrl . $path);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($data !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $body = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
    curl_close($curl);
    return ['status' => $status, 'body' => $body === false ? '' : $body, 'type' => $type ?? ''];
}

function csrfToken(string $body): string
{
    preg_match('/name="csrfToken" value="([^"]+)"/', $body, $matches);
    return $matches[1] ?? '';
}

$reportPath = 'stats/reports/report?pluginName=ReviewersControlReportReportPlugin';
$gridPath = '$$$call$$$/plugins/generic/reviewers-control-report/controllers/grid/reviewers-grid/fetch-grid';
foreach (['dbarnes' => true, 'dbuskins' => true, 'admin' => true, 'phudson' => false, 'amwandenga' => false] as $actor => $allowed) {
    $cookie = tempnam(sys_get_temp_dir(), 'rcr-http-');
    try {
        $login = requestPage('login', $cookie);
        $auth = requestPage('login/signIn', $cookie, [
            'username' => $actor,
            'password' => $actor . $actor,
            'csrfToken' => csrfToken($login['body']),
        ]);
        check($auth['status'] === 302, $actor . ': fixture login');
        $core = requestPage('stats/reports', $cookie);
        $page = requestPage($reportPath, $cookie);
        $grid = requestPage($gridPath, $cookie);
        $gridResponse = json_decode($grid['body'], true);
        $gridHtml = $gridResponse['content'] ?? '';
        $hasForm = strpos($page['body'], 'id="reviewersControlReportForm"') !== false;
        $hasGrid = strpos($gridHtml, 'reviewerscontrolreport') !== false;
        check(($core['status'] === 200) === $allowed, $actor . ': core reports access');
        check($hasForm === $allowed, $actor . ': plugin reports access');
        check($hasGrid === $allowed, $actor . ': plugin grid access');
        if (!$allowed) {
            check(strpos($gridHtml, '=1+1') === false, $actor . ': no protected reviewer data');
        }
        foreach (['reviewers', 'reviews'] as $reportType) {
            $csv = requestPage($reportPath, $cookie, [
                'csrfToken' => csrfToken($page['body']),
                'reportType' => $reportType,
                'startDateInterval' => '',
                'endDateInterval' => '',
            ]);
            $isCsv = strpos($csv['type'], 'text/comma-separated-values') === 0;
            check($isCsv === $allowed, $actor . ': ' . $reportType . ' export access');
            if ($allowed) {
                check(strpos($csv['body'], "'=1+1") !== false, $actor . ': ' . $reportType . ' formula protection');
            } else {
                check(strpos($csv['body'], '=1+1') === false, $actor . ': no exported reviewer data');
            }
        }
        if ($actor === 'dbarnes') {
            // Share the selector with Cypress: catch core markup drift without a browser.
            $browserContract = json_decode(file_get_contents(__DIR__ . '/browser-contract.json'), true);
            $pluginsGrid = requestPage('$$$call$$$/grid/settings/plugins/settings-plugin-grid/fetch-grid', $cookie);
            $pluginsResponse = json_decode($pluginsGrid['body'], true);
            $pluginsHtml = $pluginsResponse['content'] ?? '';
            $document = new DOMDocument();
            @$document->loadHTML($pluginsHtml === '' ? '<html></html>' : $pluginsHtml);
            $inputs = $document->getElementsByTagName('input');
            $matchingInputs = [];
            foreach ($inputs as $input) {
                if (strpos($input->getAttribute('id'), $browserContract['pluginEnabledInputIdPrefix']) === 0) {
                    $matchingInputs[] = $input;
                }
            }
            check(count($matchingInputs) === 1, 'Cypress activation selector matches exactly one real grid input');
            check(
                count($matchingInputs) === 1 && $matchingInputs[0]->hasAttribute('checked'),
                'Plugin activation is persisted in the real settings grid'
            );
            check(strpos($core['body'], 'ReviewersControlReportReportPlugin') !== false, 'Report is listed by the core');
            check(strpos($gridHtml, '&lt;img') !== false, 'Stored title is escaped in grid');
            check(strpos($gridHtml, '<img src=x') === false, 'Stored title cannot create an element');
            foreach (['2026-02-31', 'not-a-date', ['2026-01-01']] as $date) {
                $invalid = requestPage($reportPath, $cookie, [
                    'csrfToken' => csrfToken($page['body']),
                    'reportType' => 'reviews',
                    'startDateInterval' => $date,
                    'endDateInterval' => '',
                ]);
                check($invalid['status'] === 200, 'Invalid date does not crash');
                check(strpos($invalid['body'], 'id="reviewersControlReportForm"') !== false, 'Invalid date returns the form');
                check(strpos($invalid['body'], 'YYYY-MM-DD') !== false, 'Invalid date explains the error');
                check(strpos($invalid['body'], 'Array to string') === false, 'Invalid array is safe to redisplay');
            }
            $noCsrf = requestPage($reportPath, $cookie, ['reportType' => 'reviews']);
            check(strpos($noCsrf['type'], 'text/comma-separated-values') !== 0, 'CSRF remains required');
        }
    } finally {
        unlink($cookie);
    }
}
echo $assertions . ' assertions; ' . $failures . " failures\n";
exit($failures === 0 ? 0 : 1);
