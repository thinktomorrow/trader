<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Services;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class CookieCartReference
{
    use InteractsWithCookies;

    public function get(): OrderId
    {
        if (! $this->exists()) {
            throw new \Exception('Trying to retrieve an cart reference cookie value that doesn\'t exist.');
        }

        return OrderId::fromString($this->getCookieValue());
    }

    public function set(OrderId $orderId): void
    {
        $this->setCookieValue($orderId->get());
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
