<?php

namespace Timmsy\PiltoverClient\Facades;

use Illuminate\Support\Facades\Facade;
use Timmsy\PiltoverClient\Contracts\RiotClientContract;

/**
 * @method static array summonerByName(string $name)
 * @method static array matchById(string $matchId)
 * @method static string ping()
 */
class Piltover extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return "piltover";
    }
}
