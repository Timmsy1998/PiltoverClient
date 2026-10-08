<?php

namespace Timmsy\PiltoverClient\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Timmsy\PiltoverClient\PiltoverClientServiceProvider;

class DocumentationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [PiltoverClientServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('piltover.docs.enabled', true);
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('t', 32)));
    }

    public function test_opt_in_docs_serve_full_specification(): void
    {
        $response = $this->get('/piltover/docs/openapi.json')->assertOk()->assertHeader('Content-Type', 'application/json');
        $spec = $response->json();
        $count = 0;
        foreach ($spec['paths'] as $operations) { $count += count($operations); }
        self::assertSame(50, $count);
        self::assertArrayHasKey('post', $spec['paths']['/lol/tournament/v5/codes']);
        self::assertSame(trim(file_get_contents(__DIR__ . '/../../VERSION')), $spec['info']['version']);
        $this->get('/piltover/docs')->assertOk()->assertSee('/piltover/docs/openapi.json');
    }
}
