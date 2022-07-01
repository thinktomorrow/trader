<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class RefreshCartAction
{
    public function __construct()
    {
    }

    /**
     * Adjust the order entity so that it is up to date with
     * current prices, availability, discounts, ...
     */
    public function handle(Order $order, array $adjusters): void
    {
        Assertion::allIsInstanceOf($adjusters, Adjuster::class);

        $this->assertCartState($order);

        // Use cart adjusters to update lines, discounts, shipping, payment, shopper, ...
        foreach ($adjusters as $adjuster) {
            $adjuster->adjust($order);
        }
    }

    private function assertCartState(Order $order): void
    {
        if (! $order->inCustomerHands()) {
            throw new CannotRefreshCart('Refresh cart is not allowed. Order has state ' . $order->getOrderState()->value .' and is no longer in customer hands.');
        }
    }
}
