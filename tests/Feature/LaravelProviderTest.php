<?php

namespace Timmsy\PiltoverClient\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Timmsy\PiltoverClient\Contracts\RiotClientContract;
use Timmsy\PiltoverClient\PiltoverClientServiceProvider;
use Timmsy\PiltoverClient\Facades\Piltover;

/**
 * Boots a minimal Laravel app using Orchestra Testbench and
 * verifies the service provider, alias, facade, and helper.
 */
class LaravelProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [PiltoverClientServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Provide minimal config (no real API calls in these tests)
        $app["config"]->set("piltover.api_key", "test-key");
        $app["config"]->set("piltover.platform", "euw1");
        // regional omitted to test auto-derive behavior
    }

    public function test_container_binding_resolves_contract(): void
    {
        $client = $this->app->make(RiotClientContract::class);
        $this->assertSame("piltover-online", $client->ping());
    }

    public function test_facade_resolves_and_calls_methods(): void
    {
        $this->assertSame("piltover-online", Piltover::ping());
    }

    public function test_helper_function_returns_client(): void
    {
        $this->assertSame("piltover-online", piltover()->ping());
    }
}
