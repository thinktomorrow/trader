<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\State;

interface OrderStateReader
{
    public function getState(): string;
}
