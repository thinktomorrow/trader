<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Shipping;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\State\AbstractStateMachine;
use Thinktomorrow\Trader\Domain\Common\State\State;

final class ShippingStateMachine extends AbstractStateMachine
{
    protected function getState($model): State
    {
        Assertion::isInstanceOf($model, Shipping::class);

        return $model->getShippingState();
    }

    protected function updateState($model, State $state): void
    {
        Assertion::isInstanceOf($model, Shipping::class);

        $model->updateState($state);
    }
}
