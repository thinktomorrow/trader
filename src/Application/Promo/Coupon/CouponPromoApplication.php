<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Coupon;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderVatSnapshot;
use Thinktomorrow\Trader\Application\Promo\ApplyPromoToOrder;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromoRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;

final class CouponPromoApplication
{
    public function __construct(
        private OrderRepository                   $orderRepository,
        private EventDispatcher                   $eventDispatcher,
        private OrderPromoRepository              $orderPromoRepository,
        private ApplyPromoToOrder                 $applyPromoToOrder,
        private \Psr\Container\ContainerInterface $container,
    ) {
    }

    public function enterCoupon(EnterCoupon $enterCoupon): void
    {
        $order = $this->orderRepository->find($enterCoupon->getOrderId());

        // Find by coupon in active promo's
        if (! $promo = $this->orderPromoRepository->findOrderPromoByCouponCode($enterCoupon->getCouponCode())) {
            return;
        }

        $this->applyPromoToOrder->apply($order, $promo->getDiscounts(), $enterCoupon->getCouponCode());

        // Recalculate VAT snapshot
        $this->container->get(AdjustOrderVatSnapshot::class)->adjust($order);

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }

    public function removeCoupon(RemoveCoupon $removeCoupon): void
    {
        $order = $this->orderRepository->find($removeCoupon->getOrderId());

        $order->removeEnteredCouponCode();

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatchAll($order->releaseEvents());
    }
}
