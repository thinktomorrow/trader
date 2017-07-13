<?php

namespace Thinktomorrow\Trader\Common\Domain\State;

interface StatefulContract
{
    public function state(): string;

    public function changeState($state);
}