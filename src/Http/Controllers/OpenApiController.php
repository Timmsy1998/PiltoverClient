<?php

namespace Timmsy\PiltoverClient\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class OpenApiController extends Controller
{
    public function spec(): Response
    {
        return response(file_get_contents(dirname(__DIR__, 3) . '/resources/openapi/openapi.json'), 200, [
            'Content-Type' => 'application/json',
        ]);
    }
}
