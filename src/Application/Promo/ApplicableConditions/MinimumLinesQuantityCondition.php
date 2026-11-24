<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableConditions;

use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewProduct;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewStatus;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;

class MinimumLinesQuantityCondition implements ApplicableCondition
{
    private int $minimum_quantity;

    public static function getMapKey(): string
    {
        return MinimumLinesQuantity::getMapKey();
    }

    public function check(Order $order, DiscountableItem $discountable): bool
    {
        if (! $discountable instanceof Order) {
            return false;
        }

        return $this->meetsMinimum($order->getQuantity()->asInt());
    }

    public function evaluatePreview(?Cart $cart, PromoPreviewProduct|Cart|CartShipping|CartPayment $subject): PromoPreviewStatus
    {
        $quantity = $cart?->getQuantity() ?? 0;

        if ($this->meetsMinimum($quantity)) {
            return PromoPreviewStatus::AlreadyEligible;
        }

        $addedQuantity = $subject instanceof PromoPreviewProduct ? $subject->quantityToAdd : 0;

        if ($this->meetsMinimum($quantity + $addedQuantity)) {
            return PromoPreviewStatus::Applicable;
        }

        return PromoPreviewStatus::Conditional;
    }

    private function meetsMinimum(int $quantity): bool
    {
        return $quantity >= $this->minimum_quantity;
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $data = json_decode($state['data'], true);

        $condition = new self;
        $condition->minimum_quantity = $data['minimum_quantity'];

        return $condition;
    }
}
