<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Cart;

use Thinktomorrow\Trader\Domain\Model\Order\Entity\Order;

final class RefreshCartService
{
    /**
     * Strategy of different actions on the cart in order to shape it to the current context. The
     * sequence is important as it is handled first to last and each adjuster can influence
     * the cart state as it is passed on to the next adjuster.
     *
     * @var array
     */
    private $adjusters = [

    ];

    private Container $container;

    private CartItemsFactory $cartItemsFactory;

    public function __construct(Container $container, CartItemsFactory $cartItemsFactory)
    {
        $this->container = $container;
        $this->cartItemsFactory = $cartItemsFactory;
    }

    public function refresh(Order $order): void
    {
        // adjust all on the entity... when to refresh (after each change...) based on event
        // If changed? than save order
        // else dont save

        $order->getOrderState()->assertCartState();

        // Use cart adjusters to update items, discounts, shipping, payment, ...
        foreach ($this->adjusters as $adjuster) {
            $this->container->make($adjuster)->adjust($order);
        }
    }
}
