<?php

// Reviewed local release transaction. Publishing is a separate explicit git push.
$root = dirname(__DIR__);
chdir($root);
function command(array $command, bool $capture = false): string
{
    $process = proc_open($command, [0 => STDIN, 1 => $capture ? ['pipe', 'w'] : STDOUT, 2 => STDERR], $pipes);
    if (!is_resource($process)) { throw new RuntimeException('Cannot start command.'); }
    $output = $capture ? stream_get_contents($pipes[1]) : '';
    if ($capture) { fclose($pipes[1]); }
    if (proc_close($process) !== 0) { throw new RuntimeException('Command failed: ' . implode(' ', $command)); }
    return trim($output);
}
try {
    $bump = $argv[1] ?? 'auto';
    $dryRun = in_array('--dry-run', $argv, true);
    if (!in_array($bump, ['auto', 'patch', 'minor', 'major'], true)) { throw new RuntimeException('Usage: composer release -- [auto|patch|minor|major] [--dry-run]'); }
    $current = trim(file_get_contents('VERSION'));
    if (!preg_match('/^(\d+)\.(\d+)\.(\d+)$/D', $current, $parts)) { throw new RuntimeException('VERSION must contain a stable semantic version.'); }
    if ($bump === 'auto') {
        command(['git', 'rev-parse', '--verify', 'refs/tags/v' . $current], true);
        $log = command(['git', 'log', '--format=%B', 'v' . $current . '..HEAD'], true);
        $bump = preg_match('/^[a-z]+(?:\([^\n]+\))?!:|^BREAKING[ -]CHANGE:/m', $log) ? 'major'
            : (preg_match('/^feat(?:\([^\n]+\))?:/m', $log) ? 'minor'
            : (preg_match('/^fix(?:\([^\n]+\))?:/m', $log) ? 'patch' : null));
        if ($bump === null) { throw new RuntimeException('No releasable conventional commits since v' . $current . '; choose an explicit bump if needed.'); }
    }
    $version = match ($bump) {
        'major' => ((int) $parts[1] + 1) . '.0.0',
        'minor' => $parts[1] . '.' . ((int) $parts[2] + 1) . '.0',
        'patch' => $parts[1] . '.' . $parts[2] . '.' . ((int) $parts[3] + 1),
    };
    if ($dryRun) { echo "{$current} -> {$version} ({$bump}); no changes made.\n"; exit(0); }
    if (command(['git', 'status', '--porcelain'], true) !== '') { throw new RuntimeException('Commit or stash changes before releasing.'); }
    if (command(['git', 'tag', '--list', 'v' . $version], true) !== '') { throw new RuntimeException('Release tag already exists.'); }
    command(['composer', 'validate', '--strict']);
    command(['composer', 'test']);
    $notes = command(['git', 'log', '--format=- %s', 'v' . $current . '..HEAD'], true);
    if ($notes === '') { throw new RuntimeException('There are no commits to release.'); }
    file_put_contents('VERSION', $version . "\n");
    $changelog = file_get_contents('CHANGELOG.md');
    $entry = '## [' . $version . '] — ' . gmdate('Y-m-d') . "\n\n" . $notes . "\n\n";
    $offset = strpos($changelog, '## [');
    if ($offset === false) { throw new RuntimeException('CHANGELOG.md must contain version headings.'); }
    file_put_contents('CHANGELOG.md', substr($changelog, 0, $offset) . $entry . substr($changelog, $offset));
    command([PHP_BINARY, 'tools/generate.php']);
    command(['git', 'add', 'VERSION', 'CHANGELOG.md', 'resources/openapi/openapi.json', 'src/Http/LeagueEndpoints.php', 'src/Contracts/RiotClientContract.php', 'docs/endpoints.md']);
    command(['git', 'commit', '-m', 'chore(release): v' . $version]);
    command(['git', 'tag', '-a', 'v' . $version, '-m', 'PiltoverClient ' . $version]);
    echo "Created v{$version}. Review, then publish with git push --follow-tags.\n";
} catch (Throwable $e) { fwrite(STDERR, $e->getMessage() . "\n"); exit(1); }
