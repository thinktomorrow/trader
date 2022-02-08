<?php

namespace Thinktomorrow\Trader\Common\State;

interface Stateful
{
    public function getState(string $key): string;

    public function changeState(string $key, $state): void;
}
