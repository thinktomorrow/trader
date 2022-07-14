<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrder;
use Thinktomorrow\Trader\Domain\Common\State\AbstractStateMachine;
use Thinktomorrow\Trader\Domain\Common\State\State;

class OrderStateMachine extends AbstractStateMachine
{
    protected function getState($model): State
    {
        if ($model instanceof MerchantOrder) {
            return OrderState::from($model->getState());
        }

        Assertion::isInstanceOf($model, Order::class);

        return $model->getOrderState();
    }

    protected function updateState($model, State $state): void
    {
        Assertion::isInstanceOf($model, Order::class);

        $model->updateState($state);
    }
}
