<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts;

use Money\Money;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Common\Price\VatApplicableAmount;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;

class FixedAmountOrderSubtotalDiscount extends BaseApplicableDiscount implements OrderSubtotalDiscount
{
    private Money $amount;

    private TaxMode $taxMode;

    public static function getMapKey(): string
    {
        return FixedAmountDiscount::getMapKey();
    }

    protected static function fixedDiscountableType(): ?DiscountableType
    {
        return FixedAmountDiscount::fixedDiscountableType();
    }

    public function isApplicable(Order $order, DiscountableItem $discountable): bool
    {
        // This is a global discount: it only applies to the order total
        if (! $discountable instanceof Order) {
            return false;
        }

        return parent::isApplicable($order, $discountable);
    }

    public function getDiscountPrice(Order $order, DiscountableItem $discountable): DiscountPrice
    {
        $discountMoney = $this->amount;

        if ($this->taxMode === TaxMode::Exclusive) {
            $remainingSubtotal = $order->getSubtotalExcl()->subtract($order->getDiscountTotalExcl());
            if ($discountMoney->greaterThanOrEqual($remainingSubtotal)) {
                $discountMoney = $remainingSubtotal;
            }

            return DefaultDiscountPrice::fromExcludingVat($discountMoney);
        }

        $allocation = $this->vatAllocator->allocateOrder($order);
        $availableIncludingVat = $allocation->items()->getTotalIncludingVat()->subtract($allocation->discounts()->getTotalIncludingVat());

        if ($discountMoney->greaterThanOrEqual($availableIncludingVat)) {
            return DefaultDiscountPrice::fromIncludingVat(
                $availableIncludingVat,
                $order->getSubtotalExcl()->subtract($order->getDiscountTotalExcl()),
            );
        }

        $resolvedExcludingVat = $this->vatAllocator->resolveVatApplicableAmountExcludingVat(
            $order,
            VatApplicableAmount::includingVat($discountMoney),
        );

        return DefaultDiscountPrice::fromIncludingVat($discountMoney, $resolvedExcludingVat);
    }

    public static function fromMappedData(array $state, array $aggregateState, array $conditions, VatAllocator $vatAllocator): static
    {
        $discount = parent::fromMappedData($state, $aggregateState, $conditions, $vatAllocator);

        $data = json_decode($state['data'], true);
        $discount->amount = Cash::make($data['amount']);
        $discount->taxMode = isset($data['tax_mode']) ? TaxMode::from($data['tax_mode']) : TaxMode::Exclusive;

        return $discount;
    }
}
