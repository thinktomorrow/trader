<?php

declare(strict_types=1);

namespace Purchase\Cart\Ports;

use Optiphar\Utils\CookieValue;
use Purchase\Cart\Domain\CartReference;
use Purchase\Cart\Domain\CartReferenceSource;
use Thinktomorrow\Trader\Infrastructure\Laravel\Common\CookieSource;

class LaravelCookieCartReferenceSource extends CookieSource implements CartReferenceSource
{
    protected $lifetime = 86400; // 60 days 60 * 24 * 60

    protected $cookieKey = 'optiphar-cart-rfr';

    public function get(): CartReference
    {
        return CartReference::fromString($this->getCookieValue());
    }

    public function set(CartReference $cartReference): void
    {
        $this->setCookieValue($cartReference->get());
    }
}
