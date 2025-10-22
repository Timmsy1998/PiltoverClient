<?php

namespace Timmsy\PiltoverClient\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Timmsy\PiltoverClient\Support\Routing;

/**
 * Ensures platform → regional mapping is correct.
 */
class RoutingTest extends TestCase
{
    public function test_regional_from_platform_maps_europe(): void
    {
        $this->assertSame("europe", Routing::regionalFromPlatform("euw1"));
        $this->assertSame("europe", Routing::regionalFromPlatform("eun1"));
        $this->assertSame("europe", Routing::regionalFromPlatform("tr1"));
        $this->assertSame("europe", Routing::regionalFromPlatform("ru"));
    }

    public function test_regional_from_platform_maps_americas(): void
    {
        $this->assertSame("americas", Routing::regionalFromPlatform("na1"));
        $this->assertSame("americas", Routing::regionalFromPlatform("br1"));
        $this->assertSame("americas", Routing::regionalFromPlatform("la1"));
        $this->assertSame("americas", Routing::regionalFromPlatform("la2"));
        $this->assertSame("americas", Routing::regionalFromPlatform("oc1"));
    }

    public function test_regional_from_platform_maps_asia(): void
    {
        $this->assertSame("asia", Routing::regionalFromPlatform("kr"));
        $this->assertSame("asia", Routing::regionalFromPlatform("jp1"));
    }

    public function test_host_interpolation(): void
    {
        $tpl = "https://{platform}.api.riotgames.com";
        $this->assertSame(
            "https://euw1.api.riotgames.com",
            Routing::host($tpl, "platform", "euw1"),
        );
    }
}
