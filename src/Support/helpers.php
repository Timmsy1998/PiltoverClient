<?php

use Timmsy\PiltoverClient\Contracts\RiotClientContract;

if (!function_exists("piltover")) {
    function piltover(): RiotClientContract
    {
        return app(RiotClientContract::class);
    }
}
