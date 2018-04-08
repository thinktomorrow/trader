<?php

namespace Thinktomorrow\Trader\Common\State;

interface StatefulContract
{
    public function state(): string;

    public function changeState($state);
}
