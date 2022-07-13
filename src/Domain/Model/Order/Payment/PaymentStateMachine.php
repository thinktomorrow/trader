<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Payment;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Order\MerchantOrder\MerchantOrderPayment;
use Thinktomorrow\Trader\Domain\Common\State\AbstractStateMachine;
use Thinktomorrow\Trader\Domain\Common\State\State;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

final class PaymentStateMachine extends AbstractStateMachine
{
    private Order $order;

    protected function getState($model): State
    {
        if ($model instanceof MerchantOrderPayment) {
            return PaymentState::from($model->getPaymentState());
        }

        Assertion::isInstanceOf($model, Payment::class);

        return $model->getPaymentState();
    }

    protected function updateState($model, State $state): void
    {
        Assertion::isInstanceOf($model, Payment::class);
        Assertion::isInstanceOf($state, PaymentState::class);

        $this->order->updatePaymentState($model->paymentId, $state);
    }

    public function setOrder(Order $order): void
    {
        $this->order = $order;
    }
}
