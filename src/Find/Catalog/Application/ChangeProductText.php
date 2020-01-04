<?php

namespace Thinktomorrow\Trader\Find\Catalog\Application;

use Illuminate\Contracts\Events\Dispatcher;

class ChangeProductText
{
    /** @var Dispatcher */
    private $eventDispatcher;

    public function __construct(Dispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handle()
    {
        $this->eventDispatcher->dispatch('test');
    }
}
