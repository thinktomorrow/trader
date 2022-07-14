<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Shipping;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderShipping;
use Thinktomorrow\Trader\Domain\Common\State\AbstractStateMachine;
use Thinktomorrow\Trader\Domain\Common\State\State;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

final class ShippingStateMachine extends AbstractStateMachine
{
    private Order $order;

    protected function getState($model): State
    {
        if ($model instanceof MerchantOrderShipping) {
            return ShippingState::from($model->getShippingState());
        }

        Assertion::isInstanceOf($model, Shipping::class);

        return $model->getShippingState();
    }

    protected function updateState($model, State $state): void
    {
        Assertion::isInstanceOf($model, Shipping::class);
        Assertion::isInstanceOf($state, ShippingState::class);

        $this->order->updateShippingState($model->shippingId, $state);
    }

    public function setOrder(Order $order): void
    {
        $this->order = $order;
    }
}
