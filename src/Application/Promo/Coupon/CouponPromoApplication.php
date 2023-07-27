<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Coupon;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\ApplyPromoToOrder;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromoRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\TraderConfig;

final class CouponPromoApplication
{
    private OrderRepository $orderRepository;
    private EventDispatcher $eventDispatcher;
    private TraderConfig $config;
    private ContainerInterface $container;
    private OrderPromoRepository $orderPromoRepository;
    private ApplyPromoToOrder $applyPromoToOrder;

    public function __construct(
        TraderConfig         $config,
        ContainerInterface   $container,
        OrderRepository      $orderRepository,
        OrderPromoRepository $orderPromoRepository,
        ApplyPromoToOrder $applyPromoToOrder,
        EventDispatcher      $eventDispatcher
    ) {
        $this->orderRepository = $orderRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->config = $config;
        $this->container = $container;
        $this->orderPromoRepository = $orderPromoRepository;
        $this->applyPromoToOrder = $applyPromoToOrder;
    }

    public function enterCoupon(EnterCoupon $enterCoupon): void
    {
        $order = $this->orderRepository->find($enterCoupon->getOrderId());

        // Find by coupon in active promo's
        if (! $promo = $this->orderPromoRepository->findOrderPromoByCouponCode($enterCoupon->getCouponCode())) {
            return;
        }

        $this->applyPromoToOrder->apply($order, $promo->getDiscounts(), $enterCoupon->getCouponCode());

        //        if($promo->)
        //        $order->setEnteredCouponCode($enterCoupon->getCouponCode());

        // is applicable on order?
        // apply
        // Find by coupon in active promo's
        // is applicable on order?
        // Does
        // AU
        // Is order allowed to be manipulated?
        // apply on order

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
