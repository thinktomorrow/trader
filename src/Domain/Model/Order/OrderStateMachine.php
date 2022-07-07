<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\State\State;
use Thinktomorrow\Trader\Domain\Common\State\AbstractStateMachine;

class OrderStateMachine extends AbstractStateMachine
{
    public function __construct(array $states, array $transitions)
    {
        parent::__construct($states, $transitions);
    }

    protected function getState($model): State
    {
        Assertion::isInstanceOf($model, Order::class);

        return $model->getOrderState();
    }

    protected function updateState($model, State $state): void
    {
        Assertion::isInstanceOf($model, Order::class);

        $model->updateState($state);
    }
}
