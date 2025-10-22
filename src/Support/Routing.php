<?php

namespace Timmsy\PiltoverClient\Support;

final class Routing
{
    public static function regionalFromPlatform(string $platform): string
    {
        $platform = strtolower($platform);

        $europe = ["euw1", "eun1", "tr1", "ru"];
        $americas = ["na1", "br1", "la1", "la2", "oc1"];
        $asia = ["kr", "jp1"];

        if (in_array($platform, $europe, true)) {
            return "europe";
        }
        if (in_array($platform, $americas, true)) {
            return "americas";
        }
        if (in_array($platform, $asia, true)) {
            return "asia";
        }

        return "europe";
    }

    public static function host(
        string $template,
        string $key,
        string $value,
    ): string {
        return str_replace("{" . $key . "}", $value, $template);
    }
}
