<?php

namespace Thinktomorrow\Trader\Domain\Common\Event;

interface EventDispatcher
{
    public function dispatch(array $events): void;
}
