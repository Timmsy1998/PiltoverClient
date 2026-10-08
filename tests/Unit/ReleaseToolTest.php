<?php

namespace Timmsy\PiltoverClient\Tests\Unit;

use PHPUnit\Framework\TestCase;

class ReleaseToolTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/piltover-release-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        mkdir($this->directory . '/tools');
        mkdir($this->directory . '/resources');
        mkdir($this->directory . '/resources/openapi');
        mkdir($this->directory . '/src');
        mkdir($this->directory . '/src/Http');
        mkdir($this->directory . '/src/Contracts');
        mkdir($this->directory . '/docs');
        copy(__DIR__ . '/../../tools/release.php', $this->directory . '/tools/release.php');
        file_put_contents($this->directory . '/VERSION', "2.0.0\n");
        file_put_contents($this->directory . '/CHANGELOG.md', "# Changelog\n\n## [2.0.0]\n\nInitial fixture.\n");
        file_put_contents($this->directory . '/composer.json', json_encode([
            'name' => 'tests/release-fixture', 'description' => 'Release integration fixture', 'license' => 'MIT',
            'require' => ['php' => '^8.2'], 'scripts' => ['test' => '@php -r "exit(0);"'],
        ], JSON_PRETTY_PRINT));
        file_put_contents($this->directory . '/tools/generate.php', '<?php file_put_contents("resources/openapi/openapi.json", json_encode(["version" => trim(file_get_contents("VERSION"))]));');
        foreach (['resources/openapi/openapi.json', 'src/Http/LeagueEndpoints.php', 'src/Contracts/RiotClientContract.php', 'docs/endpoints.md'] as $file) {
            file_put_contents($this->directory . '/' . $file, '{}');
        }
        $this->runCommand(['git', 'init', '-q']);
        $this->runCommand(['git', 'config', 'user.name', 'Release Test']);
        $this->runCommand(['git', 'config', 'user.email', 'release-test@example.test']);
        $this->runCommand(['git', 'add', '.']);
        $this->runCommand(['git', 'commit', '-qm', 'chore: fixture']);
        $this->runCommand(['git', 'tag', '-a', 'v2.0.0', '-m', 'Fixture']);
    }

    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
        rmdir($this->directory);
    }

    private function runCommand(array $command, int $expectedStatus = 0): string
    {
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['redirect', 1]], $pipes, $this->directory);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
        self::assertSame($expectedStatus, proc_close($process), $output);
        return trim($output);
    }

    private function commit(string $message): void
    {
        file_put_contents($this->directory . '/change', $message);
        $this->runCommand(['git', 'add', 'change']);
        $this->runCommand(['git', 'commit', '-qm', $message]);
    }

    public function test_auto_minor_release_updates_commits_and_annotates_tag(): void
    {
        $this->commit('feat: new endpoint');
        self::assertStringContainsString('2.0.0 -> 2.1.0', $this->runCommand([PHP_BINARY, 'tools/release.php', 'auto', '--dry-run']));
        self::assertSame("2.0.0\n", file_get_contents($this->directory . '/VERSION'));
        $this->runCommand([PHP_BINARY, 'tools/release.php', 'auto']);
        self::assertSame("2.1.0\n", file_get_contents($this->directory . '/VERSION'));
        self::assertSame('tag', $this->runCommand(['git', 'cat-file', '-t', 'v2.1.0']));
        self::assertSame('chore(release): v2.1.0', $this->runCommand(['git', 'log', '-1', '--format=%s']));
        self::assertSame('', $this->runCommand(['git', 'status', '--porcelain']));
        self::assertStringContainsString('feat: new endpoint', file_get_contents($this->directory . '/CHANGELOG.md'));
        self::assertSame('2.1.0', json_decode(file_get_contents($this->directory . '/resources/openapi/openapi.json'), true)['version']);
    }

    public function test_breaking_commits_bump_major(): void
    {
        $this->commit('feat!: replace contract');
        self::assertStringContainsString('2.0.0 -> 3.0.0', $this->runCommand([PHP_BINARY, 'tools/release.php', 'auto', '--dry-run']));
    }

    public function test_fix_commits_bump_patch(): void
    {
        $this->commit('fix: routing');
        self::assertStringContainsString('2.0.0 -> 2.0.1', $this->runCommand([PHP_BINARY, 'tools/release.php', 'auto', '--dry-run']));
    }

    public function test_dirty_checkout_is_not_released(): void
    {
        file_put_contents($this->directory . '/VERSION', "2.0.0\n\n");
        self::assertStringContainsString('Commit or stash', $this->runCommand([PHP_BINARY, 'tools/release.php', 'patch'], 1));
        self::assertSame('', $this->runCommand(['git', 'tag', '--list', 'v2.0.1']));
    }
}
