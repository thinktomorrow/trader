<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Cart\Ports;

use Thinktomorrow\Trader\Common\Ports\CookieDefaults;
use Thinktomorrow\Trader\Order\Domain\OrderReference;
use Thinktomorrow\Trader\Order\Domain\OrderReferenceSource;

class OrderReferenceCookie implements OrderReferenceSource
{
    const KEY = 'trader-order-ref';

    use CookieDefaults;

    protected function getCookieKey(): string
    {
        return static::KEY;
    }

    protected function getLifetime(): int
    {
        return 60 * 24 * 60; // 60 days
    }

    public function get(): OrderReference
    {
        if (! $this->exists()) {
            throw new \Exception('Trying to retrieve an order reference cookie value that doesn\'t exist.');
        }

        return OrderReference::fromString($this->getCookieValue());
    }

    public function set(OrderReference $cartReference): void
    {
        $this->setCookieValue($cartReference->get());
    }
}
