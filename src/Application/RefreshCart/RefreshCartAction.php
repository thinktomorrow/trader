<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\RefreshCart;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Domain\Common\Context;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class RefreshCartAction
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Adjust the order entity so that it is up to date with
     * current prices, availability, discounts, ...
     */
    public function handle(Order $order, array $adjusters, Context $context): void
    {
        $this->assertCartState($order);

        // Use cart adjusters to update items, discounts, shipping, payment, ...
        foreach ($adjusters as $adjuster) {
            $this->container->get($adjuster)->adjust($order, $context);
        }
    }

    private function assertCartState(Order $order): void
    {
        var_dump('sisisi');
    }
}
