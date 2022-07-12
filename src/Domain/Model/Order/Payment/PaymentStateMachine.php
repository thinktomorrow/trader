<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Payment;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\State\State;
use Thinktomorrow\Trader\Domain\Common\State\AbstractStateMachine;

final class PaymentStateMachine extends AbstractStateMachine
{
    protected function getState($model): State
    {
        Assertion::isInstanceOf($model, Payment::class);

        return $model->getPaymentState();
    }

    protected function updateState($model, State $state): void
    {
        Assertion::isInstanceOf($model, Payment::class);

        $model->updateState($state);
    }
}
