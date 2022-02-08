<?php

namespace Thinktomorrow\Trader\Tests\Common\States;

use Thinktomorrow\Trader\Common\State\Stateful;

class StatefulStub implements Stateful
{
    const ONLINE_STATEKEY = 'online_status';
    const BUYABLE_STATEKEY = 'buy_status';

    private $online_status = 'offline';

    public function getState(string $key): string
    {
        return $this->$key;
    }

    public function changeState(string $key, $state): void
    {
        $this->$key = $state;
    }
}
