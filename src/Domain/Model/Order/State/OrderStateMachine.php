<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\State;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Domain\Common\State\AbstractStateMachine;
use Thinktomorrow\Trader\Domain\Common\State\State;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class OrderStateMachine extends AbstractStateMachine
{
    protected function getState($model): State
    {
        if ($model instanceof MerchantOrder) {
            // Get class of state so we can create the state
            $firstState = reset($this->states);

            return get_class($firstState)::fromString($model->getState());
        }

        Assertion::isInstanceOf($model, Order::class);

        return $model->getOrderState();
    }

    protected function updateState($model, State $state, array $data): void
    {
        Assertion::isInstanceOf($model, Order::class);

        $model->updateState($state, $data);
    }
}
