<?php

namespace Thinktomorrow\Trader\Tests\Common\Cash;

use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Common\Cash\Percentage;

class CashTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Reset the applied currency to avoid interference with other tests
        Cash::reset();
    }

    public function tearDown(): void
    {
        // Reset the applied currency to avoid interference with other tests
        Cash::reset();

        parent::tearDown();
    }

    /** @test */
    public function it_can_get_money_instance_with_configurable_currency()
    {
        $money = Cash::make(120, 'USD');

        $this->assertInstanceOf(Money::class, $money);
        $this->assertEquals('USD', $money->getCurrency()->getCode());
    }

    /** @test */
    public function it_can_represent_localised_money()
    {
        $cash = Cash::from(Money::EUR(120));
        $this->assertEquals('€ 1,20', $cash->locale('nl'));

        $cash = Cash::from(Money::USD(120));
        $this->assertEquals('$ 1,20', $cash->locale('nl'));
    }

    /** @test */
    public function it_can_represent_money_in_specific_format()
    {
        $this->assertEquals('1,20', Cash::from(Money::EUR(120))->toFormat(2, ','));
        $this->assertEquals('1,234.56', Cash::from(Money::EUR(123456))->toFormat(2, '.', ','));
        $this->assertEquals('1.234', Cash::from(Money::EUR(123444))->toFormat(0, '.', '.'));
        $this->assertEquals('15', Cash::from(Money::EUR(1455))->toFormat(0)); // format rounds off
    }

    /** @test */
    public function by_default_currency_code_is_used_as_symbol()
    {
        $cash = Cash::from(Money::AMD(120));
        $this->assertEquals('AMD 1,20', $cash->locale('nl'));
    }

    /** @test */
    public function it_can_get_percentage_of_money_values()
    {
        $cash = Cash::from(Money::EUR(51));
        $this->assertEquals(Percentage::fromPercent(51), $cash->asPercentage(Money::EUR(100)));
    }

    /** @test */
    public function it_can_get_percentage_with_specificity_of_2_decimals()
    {
        $cash = Cash::from(Money::EUR(55));

        // Specificity of 2 decimals by default
        $this->assertEquals(Percentage::fromPercent(45.83), $cash->asPercentage(Money::EUR(120), 2));

        // Percentage can be rounded off
        $this->assertEquals(Percentage::fromPercent(46), $cash->asPercentage(Money::EUR(120), 0));
    }

    /** @test */
    public function it_can_get_new_result_as_percentage_of_original()
    {
        $money = new Money(1000,new Currency('EUR'));
        $percentaged = Cash::from($money)->percentage(50);

        $this->assertInstanceOf(Money::class,$percentaged);
        $this->assertEquals(500,$percentaged->getAmount());

        $this->assertEquals(Money::EUR(50), Cash::from(Money::EUR(500))->percentage(10.0));
    }

    /** @test */
    public function percentage_can_be_rounded()
    {
        $money = new Money(1000,new Currency('EUR'));
        $percentaged = Cash::from($money)->percentage(50);

        $this->assertInstanceOf(Money::class,$percentaged);
        $this->assertEquals(500,$percentaged->getAmount());

        $this->assertEquals(50.00, Cash::from(Money::EUR(500))->percentage(10.0, Money::ROUND_HALF_UP,false,2));
    }

    /** @test */
    public function it_can_get_a_tax_percentage_of_gross_amount()
    {
        $money = new Money(120,new Currency('EUR'));
        $this->assertEquals(100,Cash::from($money)->subtractTaxPercentage(20)->getAmount());

        $money = new Money(1530,new Currency('EUR'));
        $this->assertEquals(1275,Cash::from($money)->subtractTaxPercentage(20)->getAmount());

        $money = new Money(1200,new Currency('EUR'));
        $this->assertEquals(960,Cash::from($money)->subtractTaxPercentage(25.0)->getAmount());
    }
}
