<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Contracts\Cache\Repository;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;

final class PromoCache
{
    private const AVAILABLE_KEY = 'trader:promos:available';

    private const MAX_TTL_SECONDS = 3600;

    public function __construct(private Repository $cache) {}

    /**
     * @param  callable(): array{0: list<Promo>, 1: ?\DateTimeImmutable}  $load
     * @return list<Promo>
     */
    public function rememberAvailable(callable $load): array
    {
        $cached = $this->cache->get(self::AVAILABLE_KEY);

        if (is_array($cached) && $cached['expires_at'] > time()) {
            return $cached['promos'];
        }

        if ($cached !== null) {
            $this->clear();
        }

        $cached = $this->cache->remember(self::AVAILABLE_KEY, self::MAX_TTL_SECONDS, function () use ($load): array {
            [$promos, $nextTransition] = $load();

            return [
                'promos' => $promos,
                'expires_at' => min(time() + self::MAX_TTL_SECONDS, $nextTransition?->getTimestamp() ?? PHP_INT_MAX),
            ];
        });

        return $cached['promos'];
    }

    public function clear(): void
    {
        $this->cache->forget(self::AVAILABLE_KEY);
    }
}
