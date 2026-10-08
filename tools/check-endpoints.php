<?php

// Fail on reference drift; never silently generate new transport/authentication rules.
require dirname(__DIR__) . '/vendor/autoload.php';
$catalogue = \Timmsy\PiltoverClient\Support\Endpoints::all();
$families = array_unique(array_map(fn ($id) => explode('.', $id)[0], array_keys($catalogue)));
$http = new \GuzzleHttp\Client(['timeout' => 30, 'connect_timeout' => 10, 'allow_redirects' => false]);
$changed = false;
try {
    foreach ($families as $family) {
        $data = json_decode((string) $http->get('https://developer.riotgames.com/api-details/' . $family)->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $html = $data['html'] ?? throw new RuntimeException('Reference response lacks HTML: ' . $family);
        preg_match_all('/<span class="http_method">\s*<a[^>]*>(GET|POST|PUT|DELETE|PATCH)<\/a>\s*<\/span>\s*<span class="path">\s*<a[^>]*>([^<]+)<\/a>\s*<\/span>/s', $html, $matches, PREG_SET_ORDER);
        if (!$matches) { throw new RuntimeException('No operations parsed for ' . $family . '; review the reference parser.'); }
        $remote = array_unique(array_map(fn ($m) => $m[1] . ' ' . trim(html_entity_decode($m[2])), $matches));
        $local = [];
        foreach ($catalogue as $id => $endpoint) {
            if (str_starts_with($id, $family . '.')) { $local[] = $endpoint['method'] . ' ' . $endpoint['path']; }
        }
        foreach (array_diff($remote, $local) as $operation) { fwrite(STDERR, "Added upstream: {$family} {$operation}\n"); $changed = true; }
        foreach (array_diff($local, $remote) as $operation) { fwrite(STDERR, "Removed upstream: {$family} {$operation}\n"); $changed = true; }
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Unable to verify Riot reference: ' . $e->getMessage() . "\n");
    exit(2);
}
if ($changed) { exit(1); }
echo count($catalogue) . " operations match the current Riot reference.\n";
