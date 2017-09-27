<?php

namespace Thinktomorrow\Trader\Tests\Price;

use Money\Money;
use Thinktomorrow\Trader\Common\Config;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Tests\UnitTestCase;

class CashTest extends UnitTestCase
{
    private $config;

    public function setUp()
    {
        parent::setUp();

        $this->config = new Config(__DIR__ . '/../../../_stubs/configStub.php');

        // Reset the applied currency to avoid interference with other tests
        Cash::reset();
    }

    public function tearDown()
    {
        // Make sure to reset the config to the proper config
        $this->config->refreshSource(__DIR__.'/../../config/trader.php');

        // Reset the applied currency to avoid interference with other tests
        Cash::reset();

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

    /** @test */
    public function it_can_represent_localised_money()
    {
        $cash = Cash::from(Money::EUR(120));
        $this->assertEquals('&euro;1.20', $cash->locale('nl_BE'));

        $cash = Cash::from(Money::USD(120));
        $this->assertEquals('&dollar;1.20', $cash->locale('nl_BE'));
    }

    /** @test */
    public function it_can_represent_money_in_specific_format()
    {
        $this->assertEquals('1,20', Cash::from(Money::EUR(120))->toFormat(2,','));
        $this->assertEquals('1,234.56', Cash::from(Money::EUR(123456))->toFormat(2,'.',','));
        $this->assertEquals('1.234', Cash::from(Money::EUR(123444))->toFormat(0,'.','.'));
        $this->assertEquals('15', Cash::from(Money::EUR(1455))->toFormat(0)); // format rounds off
    }

    /** @test */
    public function by_default_currency_code_is_used_as_symbol()
    {
        $cash = Cash::from(Money::AMD(120));
        $this->assertEquals('AMD1.20', $cash->locale('nl_BE'));
    }
}
