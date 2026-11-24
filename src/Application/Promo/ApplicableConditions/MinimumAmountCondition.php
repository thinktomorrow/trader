<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableConditions;

use Money\Money;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewProduct;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewStatus;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumAmount;

class MinimumAmountCondition implements ApplicableCondition
{
    private Money $amount;

    public static function getMapKey(): string
    {
        return MinimumAmount::getMapKey();
    }

    public function check(Order $order, DiscountableItem $discountable): bool
    {
        if (! $discountable instanceof Order) {
            return false;
        }

        return $this->meetsMinimum($order->getSubtotalExcl());
    }

    public function evaluatePreview(?Cart $cart, PromoPreviewProduct|Cart|CartShipping|CartPayment $subject): PromoPreviewStatus
    {
        $subtotalExcl = $cart?->getSubtotalExcl() ?? Money::EUR(0);

        if ($this->meetsMinimum($subtotalExcl)) {
            return PromoPreviewStatus::AlreadyEligible;
        }

        $addedSubtotal = $subject instanceof PromoPreviewProduct
            ? $subject->salePriceExcl->multiply((string) $subject->quantityToAdd)
            : Money::EUR(0);

        if ($this->meetsMinimum($subtotalExcl->add($addedSubtotal))) {
            return PromoPreviewStatus::Applicable;
        }

        if ($subject instanceof PromoPreviewProduct && $subject->quantityToAdd > 0) {
            return $addedSubtotal->greaterThan(Money::EUR(0))
                ? PromoPreviewStatus::Conditional
                : PromoPreviewStatus::Irrelevant;
        }

        return PromoPreviewStatus::Conditional;
    }

    private function meetsMinimum(Money $subtotalExcl): bool
    {
        return $subtotalExcl->greaterThanOrEqual($this->amount);
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $data = json_decode($state['data'], true);

        $condition = new static;
        $condition->amount = Cash::make($data['amount']);

        return $condition;
    }
}
