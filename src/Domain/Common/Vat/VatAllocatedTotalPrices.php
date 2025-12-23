<?php

namespace Thinktomorrow\Trader\Domain\Common\Vat;

final class VatAllocatedTotalPrices
{
    public function __construct(
        private VatAllocatedTotalPrice $items,
        private VatAllocatedTotalPrice $shipping,
        private VatAllocatedTotalPrice $payment,
        private VatAllocatedTotalPrice $discounts,
        private VatAllocatedTotalPrice $total,
    )
    {
    }

    public function items(): VatAllocatedTotalPrice
    {
        return $this->items;
    }

    public function shipping(): VatAllocatedTotalPrice
    {
        return $this->shipping;
    }

    public function payment(): VatAllocatedTotalPrice
    {
        return $this->payment;
    }

    public function discounts(): VatAllocatedTotalPrice
    {
        return $this->discounts;
    }

    public function total(): VatAllocatedTotalPrice
    {
        return $this->total;
    }
}
