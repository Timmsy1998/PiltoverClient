<?php

namespace Timmsy\PiltoverClient;

use Illuminate\Support\ServiceProvider;
use Timmsy\PiltoverClient\Contracts\RiotClientContract;
use Timmsy\PiltoverClient\Http\RiotClient;

class PiltoverClientServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . "/../config/piltover.php", "piltover");

        $this->app->singleton(RiotClientContract::class, function ($app) {
            $cfg = $app["config"]["piltover"];
            return new RiotClient(
                apiKey: (string) ($cfg["api_key"] ?? ""),
                platform: (string) ($cfg["platform"] ?? "euw1"),
                regional: $cfg["regional"] ?? null,
                platformTpl: (string) ($cfg["base_urls"]["platform"] ??
                    "https://{platform}.api.riotgames.com"),
                regionalTpl: (string) ($cfg["base_urls"]["regional"] ??
                    "https://{regional}.api.riotgames.com"),
                timeout: (float) ($cfg["timeout"] ?? 10),
            );
        });

        $this->app->alias(RiotClientContract::class, "piltover");
    }

    public function boot(): void
    {
        $this->publishes(
            [
                __DIR__ . "/../config/piltover.php" => config_path(
                    "piltover.php",
                ),
            ],
            "piltover-config",
        );

        $this->loadViewsFrom(__DIR__ . "/../resources/views", "piltover");
        $this->loadRoutesFrom(__DIR__ . "/../routes/piltover_docs.php");
    }
}
