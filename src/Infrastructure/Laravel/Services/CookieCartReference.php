<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Services;

class CookieCartReference
{
    use InteractsWithCookies;

    public function get(): string
    {
        if (! $this->exists()) {
            throw new \Exception('Trying to retrieve a cart reference cookie value that doesn\'t exist.');
        }

        return $this->getCookieValue();
    }

    public function set(string $orderId): void
    {
        $this->setCookieValue($orderId);
    }

    protected function getCookieKey(): string
    {
        return 'trader_cart';
    }

    protected function getLifetime(): int
    {
        return 60 * 24 * 60; // 60 days
    }
}
