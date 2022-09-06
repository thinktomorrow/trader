<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class RefreshCartAction
{
    /**
     * Adjust the order entity so that it is up to date with
     * current prices, availability, discounts, ...
     */
    public function handle(Order $order, array $adjusters): void
    {
        $this->assertCartState($order);

        Assertion::allIsInstanceOf($adjusters, Adjuster::class);

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
