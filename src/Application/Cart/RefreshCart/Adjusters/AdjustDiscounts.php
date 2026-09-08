<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\Promo\ApplyPromoToOrder;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromo;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromoRepository;
use Thinktomorrow\Trader\Application\Promo\PromoApplicationScope;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class AdjustDiscounts implements Adjuster
{
    private OrderPromoRepository $orderPromoRepository;

    private ApplyPromoToOrder $applyPromoToOrder;

    public function __construct(OrderPromoRepository $orderPromoRepository, ApplyPromoToOrder $applyPromoToOrder)
    {
        $this->orderPromoRepository = $orderPromoRepository;
        $this->applyPromoToOrder = $applyPromoToOrder;
    }

    public function adjust(Order $order): void
    {
        $promos = $this->resetDiscountsAndSelectPromos($order);
        $this->applySelectedPromos($order, $promos, PromoApplicationScope::All);
    }

    /** @return OrderPromo[] */
    public function resetDiscountsAndSelectPromos(Order $order): array
    {
        $this->deleteAllDiscounts($order);

        // System promos
        $systemPromos = $this->orderPromoRepository->getAvailableSystemPromos();

        // Coupon / Marketing promos
        $promos = $this->getMarketingPromos($order);

        return [
            ...$this->selectPromos($systemPromos),
            ...$this->selectPromos($promos),
        ];
    }

    /**
     * Process promos per 'group' meaning the is_combinable flag is considered within the group.
     * The combinable flag does not apply between groups.
     * This allows for system promo's to be always combined with marketing promos.
     *
     * @param  OrderPromo[]  $promos
     * @return OrderPromo[]
     */
    private function selectPromos(array $promos): array
    {
        $selectedPromos = [];

        foreach ($promos as $promo) {
            if ($selectedPromos === []) {
                $selectedPromos[] = $promo;

                continue;
            }

            $allExistingAreCombinable = count(array_filter($selectedPromos, fn (OrderPromo $selectedPromo) => $selectedPromo->isCombinable())) === count($selectedPromos);

            if (! $allExistingAreCombinable || ! $promo->isCombinable()) {
                continue;
            }

            $selectedPromos[] = $promo;
        }

        return $selectedPromos;
    }

    /** @param OrderPromo[] $promos */
    public function applySelectedPromos(Order $order, array $promos, PromoApplicationScope $scope): void
    {
        foreach ($promos as $promo) {
            $this->applyPromoToOrder->apply($order, $promo->getDiscounts(), $promo->getCouponCode(), $scope);
        }
    }

    private function deleteAllDiscounts(Order $order)
    {
        foreach ($order->getShippings() as $shipping) {
            $shipping->deleteDiscounts();
        }

        foreach ($order->getPayments() as $payment) {
            $payment->deleteDiscounts();
        }

        foreach ($order->getLines() as $line) {
            $line->deleteDiscounts();
        }

        $order->deleteDiscounts();
    }

    /**
     * @return OrderPromo[]
     */
    public function getMarketingPromos(Order $order): array
    {
        $couponPromo = $order->getEnteredCouponCode() ? $this->orderPromoRepository->findOrderPromoByCouponCode($order->getEnteredCouponCode()) : null;

        if (! $couponPromo) {
            $order->removeEnteredCouponCode();
        }

        $promos = array_filter(
            $this->orderPromoRepository->getAvailableOrderPromos(),
            fn (OrderPromo $promo) => ! $promo->isSystemPromo()
        );

        // Sort marketing promos by highest impact
        usort($promos, function (OrderPromo $a, OrderPromo $b) use ($order) {
            $aValue = $a->getCombinedDiscountPrice($order)->getExcludingVat()->getAmount();
            $bValue = $b->getCombinedDiscountPrice($order)->getExcludingVat()->getAmount();

            return $bValue <=> $aValue; // DESC
        });

        // If coupon is given on cart, we'll refresh that promo first
        if ($couponPromo) {
            return array_merge([$couponPromo], $promos);
        }

        return $promos;
    }
}
