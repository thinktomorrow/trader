<?php

namespace Thinktomorrow\Trader\Domain\Model\Order\State;

use Psr\Container\ContainerInterface;

class OrderStateFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function make(string $state): OrderState
    {
        return $this->container->get(OrderState::class)::fromString($state);
    }
}
