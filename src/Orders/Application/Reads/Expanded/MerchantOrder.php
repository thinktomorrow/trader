<?php

namespace Thinktomorrow\Trader\Orders\Application\Reads\Expanded;

/**
 * Order presenter for merchant.
 */
interface MerchantOrder
{
    public function id(): string;

    public function reference(): string;

    public function items(): array;

    public function discounts(): array;

    public function total(): string;

    public function subtotal(): string;

    public function tax(): string;

    public function taxRates(): array;

    public function discountTotal(): string;

    public function shippingTotal(): string;

    public function paymentTotal(): string;

    public function confirmedAt(): string;
}
