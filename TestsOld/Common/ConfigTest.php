<?php

use Thinktomorrow\Trader\Common\Config;
use Thinktomorrow\Trader\TestsOld\TestCase;

class ConfigTest extends TestCase
{
    private $config;

    public function setUp()
    {
        parent::setUp();

        $this->config = new Config(__DIR__.'/../Stubs/configStub.php');
    }

    public function tearDown()
    {
        // Make sure to reset the config to the proper config
        $this->config->refreshSource(__DIR__.'/../../config/trader.php');

        parent::tearDown();
    }

    /** @test */
    public function if_key_not_found_it_returns_null()
    {
        $this->assertNull($this->config->get('unknown'));
    }

    /** @test */
    public function default_can_be_set_at_runtime()
    {
        $this->assertEquals('foozball', $this->config->get('unknown', 'foozball'));
    }

    /** @test */
    public function it_can_get_value_from_config()
    {
        $this->assertEquals('foobar', $this->config->get('fool'));
    }
}
