<?php
declare(strict_types=1);

namespace Tests\Unit\Common\Cash;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;

class PriceValueTest extends TestCase
{
    /** @test */
    public function it_can_make_price_including_vat()
    {
        $object = PriceValueStub::fromMoney(
            Money::EUR(120), TaxRate::fromString('20'), true
        );

        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
    }

    /** @test */
    public function it_can_make_price_excluding_vat()
    {
        $object = PriceValueStub::fromMoney(
            Money::EUR(100), TaxRate::fromString('20'), false
        );

        $this->assertEquals(Money::EUR(100), $object->getExcludingVat());
        $this->assertEquals(Money::EUR(120), $object->getIncludingVat());
    }
}
