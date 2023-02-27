<?php

namespace Thinktomorrow\Trader\Domain\Model\Order\State;

use Thinktomorrow\Trader\Domain\Common\State\State;

interface OrderState extends State
{
    public function inCustomerHands(): bool;

    public static function customerStates(): array;
}
