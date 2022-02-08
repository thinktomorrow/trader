<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Common\Cash;

use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    /** @test */
    public function it_can_be_called()
    {
        $money = new Money(500, new Currency('EUR'));

        $this->assertInstanceOf(Money::class, $money);
    }

    /** @test */
    public function it_can_be_called_with_shorthand()
    {
        $money = Money::USD(530);

        $this->assertInstanceOf(Money::class, $money);

        $this->assertEquals('USD', $money->getCurrency()->getCode());
    }
}
