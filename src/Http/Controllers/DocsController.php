<?php

namespace Timmsy\PiltoverClient\Http\Controllers;

use Illuminate\Routing\Controller;

class DocsController extends Controller
{
    public function show()
    {
        return view("piltover::docs", [
            "specUrl" => route("piltover.docs.spec"),
            "title" => "PiltoverClient API Docs",
        ]);
    }
}
