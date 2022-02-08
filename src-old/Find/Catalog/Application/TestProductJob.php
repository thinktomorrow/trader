<?php

namespace Find\Catalog\Application;

use Illuminate\Contracts\Queue\ShouldQueue;
use function Thinktomorrow\Trader\Find\Catalog\Application\dd;

class TestProductJob implements ShouldQueue
{
    public function handle()
    {
        dd('hallo');
    }
}
