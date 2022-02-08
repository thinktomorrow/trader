<?php

namespace Thinktomorrow\Trader\Order\Domain;

use Thinktomorrow\Trader\Common\State\StateMachine;
use Thinktomorrow\Trader\Order\Domain\Exceptions\OrderNotInCartState;

interface OrderStateMachine extends StateMachine
{
    /** @throws OrderNotInCartState */
    public function assertCartState(string $state): void;
}
