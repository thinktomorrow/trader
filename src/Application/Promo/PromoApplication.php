<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\TraderConfig;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\ApplicablePromoRepository;

final class PromoApplication
{
    private OrderRepository $orderRepository;
    private EventDispatcher $eventDispatcher;
    private TraderConfig $config;
    private ContainerInterface $container;
    private ApplicablePromoRepository $applicablePromoRepository;

    public function __construct(
        TraderConfig              $config,
        ContainerInterface        $container,
        OrderRepository           $orderRepository,
        ApplicablePromoRepository $applicablePromoRepository,
        EventDispatcher           $eventDispatcher
    ) {
        $this->orderRepository = $orderRepository;
        $this->eventDispatcher = $eventDispatcher;
        $this->config = $config;
        $this->container = $container;
        $this->applicablePromoRepository = $applicablePromoRepository;
    }

    public function enterCoupon(EnterCoupon $enterCoupon): void
    {
        $order = $this->orderRepository->find($enterCoupon->getOrderId());

        // Find by coupon in active promo's
        if(!$promo = $this->applicablePromoRepository->findActivePromoByCouponCode($enterCoupon->getCouponCode())) {
            return;
        }

        $promo->apply($order); // Let the discount determine if it applies to order, line, shipping or other?



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
}
