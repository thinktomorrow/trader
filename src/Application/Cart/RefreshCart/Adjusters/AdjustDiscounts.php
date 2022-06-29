<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\ApplyPromoToOrder;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromo;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromoRepository;
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
        // Dang
        $this->deleteAllDiscounts($order);

        // Keep track of the promos that are considered combinable with other combinable promos
        $combinablePromoIds = [];

        // If coupon is given on cart, we'll refresh that promo first
        if ($order->getEnteredCouponCode() && $couponPromo = $this->orderPromoRepository->findOrderPromoByCouponCode($order->getEnteredCouponCode())) {
            $this->applyPromoToOrder->apply($order, $couponPromo->getDiscounts());

            if ($couponPromo->isCombinable()) {
                $combinablePromoIds[] = $couponPromo->promoId;
            }
        } else {
            $order->removeEnteredCouponCode();
        }

        $promos = $this->orderPromoRepository->getAvailableOrderPromos();

        // Sort them by highest impact
        usort($promos, fn (OrderPromo $promo) => $promo->getCombinedDiscountTotal($order)->getIncludingVat()->getAmount());

        foreach ($promos as $promo) {
            if ($promo->isCombinable()) {
                $combinablePromoIds[] = $promo->promoId;
            }

            // Check if existing promos are combinable
            if (! $this->areExistingPromosCombinable($order, $combinablePromoIds)) {
                break;
            }

            // Check if this promo is combinable when there are already promos on the order present
            if ($this->hasPromo($order) && ! $promo->isCombinable()) {
                continue;
            }

            $this->applyPromoToOrder->apply($order, $promo->getDiscounts(), $promo->getCouponCode());
        }
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
}
