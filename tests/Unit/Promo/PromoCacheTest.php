<?php

declare(strict_types=1);

namespace Tests\Unit\Promo;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\PromoCache;

final class PromoCacheTest extends TestCase
{
    public function test_it_reuses_promos_until_cleared(): void
    {
        $cache = new PromoCache(new Repository(new ArrayStore));
        $loads = 0;
        $load = function () use (&$loads): array {
            $loads++;

            return [[], null];
        };

        $cache->rememberAvailable($load);
        $cache->rememberAvailable($load);
        $this->assertSame(1, $loads);

        $cache->clear();
        $cache->rememberAvailable($load);
        $this->assertSame(2, $loads);
    }

    public function test_it_refreshes_when_a_promo_reaches_its_start_or_end_time(): void
    {
        $cache = new PromoCache(new Repository(new ArrayStore));
        $loads = 0;

        $load = function () use (&$loads): array {
            $loads++;

            return [[], new \DateTimeImmutable('@'.(time() - 1))];
        };

        $cache->rememberAvailable($load);
        $cache->rememberAvailable($load);

        $this->assertSame(2, $loads);
    }
}
