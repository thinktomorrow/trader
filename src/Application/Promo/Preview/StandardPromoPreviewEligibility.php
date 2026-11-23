<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

use Money\Money;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PercentageOffDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\SalePriceSystemDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;

final class StandardPromoPreviewEligibility implements PromoPreviewEligibility
{
    public function supports(string $discountKey): bool
    {
        return in_array($discountKey, [
            SalePriceSystemDiscount::getMapKey(),
            FixedAmountDiscount::getMapKey(),
            PercentageOffDiscount::getMapKey(),
        ], true);
    }

    public function assess(Promo $promo, Discount $discount, PromoPreviewProduct $product, int $quantity, ?Cart $cart): PromoPreviewStatus
    {
        if ($discount instanceof SalePriceSystemDiscount) {
            return $product->isOnSale() ? PromoPreviewStatus::Applicable : PromoPreviewStatus::Irrelevant;
        }

        $subtotal = ($cart?->getSubtotalExcl() ?? Money::EUR(0))
            ->add($product->salePriceExcl->multiply((string) $quantity));
        $lineQuantity = ($cart?->getQuantity() ?? 0) + $quantity;

        foreach ($discount->getConditions() as $condition) {
            $data = json_decode($condition->getMappedData()['data'], true, flags: JSON_THROW_ON_ERROR);

            if ($condition instanceof MinimumAmount && $subtotal->lessThan(Money::EUR($data['amount']))) {
                return PromoPreviewStatus::Conditional;
            }

            if ($condition instanceof MinimumLinesQuantity && $lineQuantity < (int) $data['minimum_quantity']) {
                return PromoPreviewStatus::Conditional;
            }

            if (! $condition instanceof MinimumAmount && ! $condition instanceof MinimumLinesQuantity) {
                return PromoPreviewStatus::Conditional;
            }
        }

        return PromoPreviewStatus::Applicable;
    }
}
