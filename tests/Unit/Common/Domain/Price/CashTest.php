<?php

namespace Thinktomorrow\Trader\Tests\Unit\Price;

use Money\Money;
use Thinktomorrow\Trader\Common\Config;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class CashTest extends UnitTestCase
{
    private $config;

    public function setUp()
    {
        parent::setUp();

        $this->config = new Config(__DIR__.'/../../../Stubs/configStub.php');

        // Reset the applied currency to avoid interference with other tests
        Cash::resetCurrency();
    }

    public function tearDown()
    {
        // Make sure to reset the config to the proper config
        $this->config->refreshSource(__DIR__.'/../../config/trader.php');

        // Reset the applied currency to avoid interference with other tests
        Cash::resetCurrency();

        parent::tearDown();
    }

    /** @test */
    public function it_can_get_money_instance_with_configurable_currency()
    {
        $this->assertEquals('USD', $this->config->get('currency'));

        $money = Cash::make(120);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertEquals('USD', $money->getCurrency()->getCode());
    }
}
