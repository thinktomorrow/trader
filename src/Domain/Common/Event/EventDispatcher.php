<?php

namespace Thinktomorrow\Trader\Domain\Common\Event;

interface EventDispatcher
{
    public function dispatchAll(array $events): void;
}
