<?php

namespace Thinktomorrow\Trader\Find\Catalog\Application;

use Illuminate\Contracts\Queue\ShouldQueue;

class TestProductJob implements ShouldQueue
{
    public function handle()
    {
        dd('hallo');
    }
}
