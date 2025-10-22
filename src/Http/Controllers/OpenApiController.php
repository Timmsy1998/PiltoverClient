<?php

namespace Timmsy\PiltoverClient\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class OpenApiController extends Controller
{
    public function spec(): Response
    {
        $path = __DIR__ . "/../../../resources/openapi/openapi.json";
        if (!file_exists($path)) {
            $path = __DIR__ . "/../../../resources/openapi/openapi.yaml";
            $mime = "application/yaml";
        } else {
            $mime = "application/json";
        }

        return response(file_get_contents($path), 200, [
            "Content-Type" => $mime,
        ]);
    }
}
