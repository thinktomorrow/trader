<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;

/**
 * Refreshes cart discounts based on the available promotions.
 *
 * Collects automatic and optional coupon promotions as candidates. Delegates
 * the selection of the best compatible combination to SelectBestPromoCombination
 * so discounts are recalculated whenever the cart is refreshed.
 */
final class AdjustDiscounts implements Adjuster
{
    public function __construct(
        private PromoRepository $promoRepository,
        private SelectBestPromoCombination $selectBestPromoCombination,
    ) {}

    public function adjust(Order $order): void
    {
        // Look up the promotion associated with the coupon code entered on the order.
        $couponPromo = $order->getEnteredCouponCode()
            ? $this->promoRepository->findAvailableByCouponCode($order->getEnteredCouponCode())
            : null;

        // Remove the entered code if no available coupon promotion matches it.
        if ($order->getEnteredCouponCode() && ! $couponPromo) {
            $order->removeEnteredCouponCode();
        }

        // Gather all promotions eligible for discount selection.
        $availablePromos = [
            ...$this->promoRepository->getAvailableAutomaticPromos(),
            ...($couponPromo ? [$couponPromo] : []),
        ];

        // Select the best compatible combination and apply its discounts.
        $this->selectBestPromoCombination->apply($order, $availablePromos);
    }
}
