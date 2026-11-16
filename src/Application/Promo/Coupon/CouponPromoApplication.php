<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Coupon;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustDiscounts;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderPricingSnapshot;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;

final class CouponPromoApplication
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EventDispatcher $eventDispatcher,
        private PromoRepository $promoRepository,
        private ContainerInterface $container,
    ) {}

    public function enterCoupon(EnterCoupon $enterCoupon): void
    {
        $order = $this->orderRepository->find($enterCoupon->getOrderId());

        // Find by coupon in active promo's
        if (! $promo = $this->promoRepository->findAvailableByCouponCode($enterCoupon->getCouponCode())) {
            return;
        }

        $order->setEnteredCouponCode($enterCoupon->getCouponCode());
        $this->container->get(AdjustDiscounts::class)->adjust($order);

        // Recalculate the pricing snapshot after changing the applied discounts.
        $this->container->get(AdjustOrderPricingSnapshot::class)->adjust($order);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function removeCoupon(RemoveCoupon $removeCoupon): void
    {
        $order = $this->orderRepository->find($removeCoupon->getOrderId());

        $order->removeEnteredCouponCode();

        $this->container->get(AdjustDiscounts::class)->adjust($order);
        $this->container->get(AdjustOrderPricingSnapshot::class)->adjust($order);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }
}
