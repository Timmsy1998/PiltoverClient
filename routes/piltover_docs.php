<?php

use Illuminate\Support\Facades\Route;
use Timmsy\PiltoverClient\Http\Controllers\DocsController;
use Timmsy\PiltoverClient\Http\Controllers\OpenApiController;

$cfg = config("piltover.docs");

if (!empty($cfg["enabled"])) {
    Route::middleware($cfg["middleware"] ?? ["web"])->group(function () use (
        $cfg,
    ) {
        Route::get($cfg["path"], [DocsController::class, "show"])->name(
            "piltover.docs",
        );

        Route::get($cfg["spec_path"] ?? "piltover/docs/openapi.json", [
            OpenApiController::class,
            "spec",
        ])->name("piltover.docs.spec");
    });
}
