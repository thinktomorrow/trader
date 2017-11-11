<?php

namespace Thinktomorrow\Trader\Orders\Domain\Read;

use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;

/**
 * Order presenter for cart.
 */
interface Cart
{
    public function id(): string;

    public function reference(): string;

    public function isBusiness(): bool;

    public function size(): int;

    public function empty(): bool;

    public function items(): array;

    public function discounts(): AppliedDiscountCollection;

    public function shippingMethodId(): int;

    public function shippingRuleId(): int;

    // TODO
    public function freeShipment(): bool;

    public function tax(): string;

    public function taxRates(): array;

    public function total(): string;

    public function subtotal(): string;

    public function discountTotal(): string;

    public function shippingTotal(): string;

    public function paymentTotal(): string;
}
