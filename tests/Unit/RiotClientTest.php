<?php

namespace Timmsy\PiltoverClient\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Timmsy\PiltoverClient\Http\RiotClient;

class RiotClientTest extends TestCase
{
    public function test_ping(): void
    {
        $client = new RiotClient("fake-key");
        $this->assertSame("piltover-online", $client->ping());
    }
}
