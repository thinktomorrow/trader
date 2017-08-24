<?php

namespace Thinktomorrow\Trader\Orders\Application\Reads\Cart;

use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRuleId;

/**
 * Order presenter for cart.
 */
interface Cart
{
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