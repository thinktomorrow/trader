<?php

namespace Thinktomorrow\Trader\Common\States;

interface StatefulContract
{
    public function stateOf(string $key);

    public function changeStateOf(string $key, $state);
}
