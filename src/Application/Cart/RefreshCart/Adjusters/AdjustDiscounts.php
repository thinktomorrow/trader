<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\Promo\ApplyPromoToOrder;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromo;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromoRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class AdjustDiscounts implements Adjuster
{
    private ContainerInterface $container;
    private OrderPromoRepository $orderPromoRepository;
    private ApplyPromoToOrder $applyPromoToOrder;

    public function __construct(ContainerInterface $container, OrderPromoRepository $orderPromoRepository, ApplyPromoToOrder $applyPromoToOrder)
    {
        $this->container = $container;
        $this->orderPromoRepository = $orderPromoRepository;
        $this->applyPromoToOrder = $applyPromoToOrder;
    }

    public function adjust(Order $order): void
    {
        // Dang
        $this->deleteAllDiscounts($order);

        // System promos
        $systemPromos = $this->orderPromoRepository->getAvailableSystemPromos();

        // Coupon / Marketing promos
        $promos = $this->getMarketingPromos($order);

        $this->processPromos($order, $systemPromos);
        $this->processPromos($order, $promos);
    }

    /**
     * Process promos per 'group' meaning the is_combinable flag is considered within the group.
     * The combinable flag does not apply between groups.
     * This allows for system promo's to be always combined with marketing promos.
     *
     * @param Order $order
     * @param array $promos
     * @return void
     */
    private function processPromos(Order $order, array $promos)
    {
        $processedPromoIds = [];

        // Keep track of the promos that are considered combinable with other combinable promos
        $processedCombinablePromoIds = [];

        foreach ($promos as $promo) {

            // First promo in the group always applies
            if (count($processedPromoIds) === 0) {
                $this->applyPromo($order, $promo, $processedPromoIds, $processedCombinablePromoIds);

                continue;
            }

            // Check if all existing promos are combinable
            $allExistingAreCombinable = count($processedPromoIds) === count($processedCombinablePromoIds);

            if (! $allExistingAreCombinable || ! $promo->isCombinable()) {
                continue;
            }

            $this->applyPromo($order, $promo, $processedPromoIds, $processedCombinablePromoIds);
        }
    }

    private function applyPromo(Order $order, OrderPromo $promo, array &$processedPromoIds, array &$processedCombinablePromoIds): void
    {
        $processedPromoIds[] = $promo->promoId;

        if ($promo->isCombinable()) {
            $processedCombinablePromoIds[] = $promo->promoId;
        }

        $this->applyPromoToOrder->apply($order, $promo->getDiscounts(), $promo->getCouponCode());
    }

    private function deleteAllDiscounts(Order $order)
    {
        foreach ($order->getShippings() as $shipping) {
            $shipping->deleteDiscounts();
        }

        foreach ($order->getLines() as $line) {
            $line->deleteDiscounts();
        }

        $order->deleteDiscounts();
    }

    private function areExistingPromosCombinable(Order $order, array $combinablePromoIds)
    {
        foreach ($this->getExistingPromoIds($order) as $existingPromoId) {
            if (! in_array($existingPromoId, $combinablePromoIds)) {
                return false;
            }
        }

        return true;
    }

    private function hasPromo(Order $order): bool
    {
        foreach ($order->getShippings() as $shipping) {
            if (count($shipping->getDiscounts()) > 0) {
                return true;
            }
        }

        foreach ($order->getLines() as $line) {
            if (count($line->getDiscounts()) > 0) {
                return true;
            }
        }

        if (count($order->getDiscounts()) > 0) {
            return true;
        }

        return false;
    }

    private function getExistingPromoIds(Order $order)
    {
        $promoIds = [];

        foreach ($order->getShippings() as $shipping) {
            foreach ($shipping->getDiscounts() as $discount) {
                $promoIds[] = $discount->promoId;
            };
        }

        foreach ($order->getLines() as $line) {
            foreach ($line->getDiscounts() as $discount) {
                $promoIds[] = $discount->promoId;
            };
        }

        foreach ($order->getDiscounts() as $discount) {
            $promoIds[] = $discount->promoId;
        };

        return array_unique($promoIds);
    }

    /**
     * @param Order $order
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
