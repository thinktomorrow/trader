<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Domain\Common\Context;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingState;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingTotal;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Entity\OrderRepository;
use Thinktomorrow\Trader\Application\Cart\Adjusters\AdjustShippingTotal;
use Thinktomorrow\Trader\Domain\Model\Shipping\Entity\ShippingRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Details\OrderDetailsRepository;

final class CartApplication
{
    private ContainerInterface $container;
    private ProductRepository $productRepository;
    private OrderRepository $orderRepository;
    private OrderDetailsRepository $orderDetailsRepository;
    private ShippingRepository $shippingRepository;
    private EventDispatcher $eventDispatcher;

    public function __construct(ContainerInterface $container, ProductRepository $productRepository, OrderRepository $orderRepository, OrderDetailsRepository $orderDetailsRepository, ShippingRepository $shippingRepository, EventDispatcher $eventDispatcher)
    {
        $this->container = $container;
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
        $this->orderDetailsRepository = $orderDetailsRepository;
        $this->shippingRepository = $shippingRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addLine(AddLine $addLine): void
    {
        $order = $this->orderRepository->find($addLine->getOrderId());

        $order->addOrUpdateLine(
            $addLine->getLineNumber(),
            $addLine->getProductId(),
            $addLine->getQuantity()
        );

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatch($order->releaseEvents());

        // Trigger refresh (should be after event)
        $this->refreshCart(new RefreshCart($order->orderId->get(), [
            AdjustShippingTotal::class,
        ], new Context(), // TODO: create testcontext?
        ));
    }

    public function chooseShipping(ChooseShipping $chooseShipping): void
    {
        $order = $this->orderRepository->find($chooseShipping->getOrderId());
        $shipping = $this->shippingRepository->find($chooseShipping->getShippingId());

        $order->updateShipping(
            $shipping->shippingId,
            ShippingState::initialized,
            ShippingTotal::fromScalars(0, 'EUR', '6', true),
            []
        );

        // Maybe do the refresh here???? before the save.

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatch($order->releaseEvents());

        // Trigger refresh (should be after event)
        $this->refreshCart(new RefreshCart($order->orderId->get(), [
            AdjustShippingTotal::class,
        ], new Context(), // TODO: create testcontext?
        ));
    }

    public function refreshCart(RefreshCart $refreshCart): void
    {
        $orderDetails = $this->orderDetailsRepository->find($refreshCart->getOrderId());
        $order = $this->orderRepository->find($refreshCart->getOrderId());

        $this->assertCartState($orderDetails);

        // Use cart adjusters to update items, discounts, shipping, payment, ...
        foreach ($refreshCart->getAdjusters() as $adjuster) {
            $this->container->get($adjuster)->adjust($order, $orderDetails, $refreshCart->getContext());
        }

        $this->orderRepository->save($order);

        $this->eventDispatcher->dispatch($order->releaseEvents());

        // Feels wrong.... what about discounts... and changing the subtotal,...
//        $shipping = $this->shippingTotalService->get(
//            $chooseShipping->getShippingId(),
//            $orderDetails->getSubTotal(),
//            $orderDetails->getShippingAddress()->getCountry(),
//        );
    }

    private function assertCartState(\Thinktomorrow\Trader\Domain\Model\Order\Details\OrderDetails $orderDetails): void
    {

    }
}
