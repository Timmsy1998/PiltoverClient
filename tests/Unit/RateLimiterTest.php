<?php

namespace Timmsy\PiltoverClient\Tests\Unit;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Timmsy\PiltoverClient\RateLimit\InMemoryRateLimiter;
use Timmsy\PiltoverClient\RateLimit\FileRateLimiter;

class RateLimiterTest extends TestCase
{
    public function test_multiple_windows_method_isolation_and_reservations(): void
    {
        $limiter = new InMemoryRateLimiter();
        $limiter->observe('scope', 'method', new Response(200, [
            'X-App-Rate-Limit' => '10:1,100:120', 'X-App-Rate-Limit-Count' => '1:1,1:120',
            'X-Method-Rate-Limit' => '2:10', 'X-Method-Rate-Limit-Count' => '1:10',
        ]), 100);
        self::assertSame(0.0, $limiter->reserve('scope', 'method', 100));
        self::assertSame(10.0, $limiter->reserve('scope', 'method', 100));
        self::assertSame(0.0, $limiter->reserve('scope', 'other', 100));
        self::assertSame(0.0, $limiter->reserve('other-scope', 'method', 100));
        self::assertSame(0.0, $limiter->reserve('scope', 'method', 110));
    }

    public function test_file_limiter_shares_windows_across_instances(): void
    {
        $directory = sys_get_temp_dir() . '/piltover-test-' . bin2hex(random_bytes(8));
        try {
            $one = new FileRateLimiter($directory); $two = new FileRateLimiter($directory);
            $one->observe('scope', 'method', new Response(200, ['X-App-Rate-Limit' => '2:10', 'X-App-Rate-Limit-Count' => '1:10']), 100);
            self::assertSame(0.0, $two->reserve('scope', 'method', 100));
            self::assertSame(10.0, $one->reserve('scope', 'different', 100));
            self::assertSame(0.0, $two->reserve('scope', 'method', 110));
        } finally {
            foreach (glob($directory . '/*') ?: [] as $file) { unlink($file); }
            rmdir($directory);
        }
    }

    public function test_http_date_and_missing_retry_after(): void
    {
        self::assertSame(5.0, InMemoryRateLimiter::retryAfter(new Response(429, ['Retry-After' => gmdate('D, d M Y H:i:s \G\M\T', 105)]), 100));
        self::assertSame(1.0, InMemoryRateLimiter::retryAfter(new Response(429), 100));
    }
}
