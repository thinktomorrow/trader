<?php

namespace Thinktomorrow\Trader\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [\Thinktomorrow\Trader\TraderServiceProvider::class];
    }
}
