<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\VatRate;

use Money\Money;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Common\Price\HasAuthoritativeAmount;
use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

final class CalculateServicePricesIncludingVat
{
    public function __construct(private VatAllocator $vatAllocator) {}

    /**
     * Respect an entered gross cost and allocate exclusive costs and discounts over the
     * order's product VAT rates before subtracting the discount from the original cost.
     *
     * @return array{cost: Money, discount: Money, total: Money}
     */
    public function forOrder(Order $order, HasAuthoritativeAmount&Price $cost, HasAuthoritativeAmount&Price $discount): array
    {
        $costIncludingVat = $this->vatAllocator->allocateServicePrice($order, $cost)->getTotalIncludingVat();
        $discountIncludingVat = $this->vatAllocator->allocateServicePrice($order, $discount)->getTotalIncludingVat();

        return [
            'cost' => $costIncludingVat,
            'discount' => $discountIncludingVat,
            'total' => $costIncludingVat->subtract($discountIncludingVat),
        ];
    }
}
