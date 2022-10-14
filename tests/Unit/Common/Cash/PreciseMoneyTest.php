<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Cash;

use Money\Money;
use Money\Currency;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Cash\PreciseMoney;

class PreciseMoneyTest extends TestCase
{
    /** @test */
    public function it_can_be_called()
    {
        $preciseMoney = new PreciseMoney(Money::EUR(50001));

        $this->assertEquals(Money::EUR(50001), $preciseMoney->getPreciseMoney());
        $this->assertEquals(Money::EUR(5), $preciseMoney->getMoney());
    }

    /** @test */
    public function it_can_be_called_with_specific_precision()
    {
        $preciseMoney = new PreciseMoney(Money::EUR(500010000), 6);

        $this->assertEquals(Money::EUR(500010000), $preciseMoney->getPreciseMoney());
        $this->assertEquals(Money::EUR(500), $preciseMoney->getMoney());
    }

    /** @test */
    public function it_will_be_rounded()
    {
        $preciseMoney = new PreciseMoney(Money::EUR(50057), 2);

        $this->assertEquals(Money::EUR(50057), $preciseMoney->getPreciseMoney());
        $this->assertEquals(Money::EUR(501), $preciseMoney->getMoney());
    }

    /** @test */
    public function it_can_have_no_precision()
    {
        $preciseMoney = new PreciseMoney(Money::EUR(50057), 0);

        $this->assertEquals(Money::EUR(50057), $preciseMoney->getPreciseMoney());
        $this->assertEquals(Money::EUR(50057), $preciseMoney->getMoney());
    }

    /** @test */
    public function it_can_be_called_from_amount()
    {
        $preciseMoney = PreciseMoney::calculateFromFloat(500.01, 4);

        $this->assertEquals(Money::EUR(5000100), $preciseMoney->getPreciseMoney());
        $this->assertEquals(Money::EUR(500), $preciseMoney->getMoney());

        $preciseMoney = PreciseMoney::calculateFromFloat(500.57,4, 'USD');

        $this->assertEquals(Money::USD(5005700), $preciseMoney->getPreciseMoney());
        $this->assertEquals(Money::USD(501), $preciseMoney->getMoney());
    }

    /** @test */
    public function it_can_add_precise_amount()
    {
        $preciseMoney = PreciseMoney::calculateFromFloat(500.01, 4);
        $otherPreciseMoney = PreciseMoney::calculateFromFloat(400.01, 4);

        $this->assertEquals(Money::EUR(9000200), $preciseMoney->add($otherPreciseMoney)->getPreciseMoney());
        $this->assertEquals(Money::EUR(900), $preciseMoney->add($otherPreciseMoney)->getMoney());
    }

    /** @test */
    public function it_cannot_add_precise_amount_with_different_precisions()
    {
        $this->expectException(\Exception::class);

        $preciseMoney = PreciseMoney::calculateFromFloat(500.01, 4);
        $otherPreciseMoney = PreciseMoney::calculateFromFloat(400.01, 2);

        $preciseMoney->add($otherPreciseMoney)->getPreciseMoney();
    }

    /** @test */
    public function it_can_subtract_precise_amount_with_different_precisions()
    {
        $this->expectException(\Exception::class);

        $preciseMoney = PreciseMoney::calculateFromFloat(500.01, 4);
        $otherPreciseMoney = PreciseMoney::calculateFromFloat(400.01, 2);

        $preciseMoney->subtract($otherPreciseMoney)->getPreciseMoney();
    }

    /** @test */
    public function it_can_subtract_precise_amount()
    {
        $preciseMoney = PreciseMoney::calculateFromFloat(500.03, 4);
        $otherPreciseMoney = PreciseMoney::calculateFromFloat(400.01, 4);

        $this->assertEquals(Money::EUR(1000200), $preciseMoney->subtract($otherPreciseMoney)->getPreciseMoney());
        $this->assertEquals(Money::EUR(100), $preciseMoney->subtract($otherPreciseMoney)->getMoney());
    }
}
