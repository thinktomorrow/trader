<?php

namespace Tests\Unit\Common\Vat;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class VatPercentageTest extends TestCase
{
    public function test_it_rejects_more_than_six_decimal_places(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VatPercentage::fromString('6.0000001');
    }

    public function test_it_can_be_set_by_integer()
    {
        $taxRate = VatPercentage::fromString('21');

        $this->assertEquals(0.21, $taxRate->toPercentage()->toDecimal());
        $this->assertEquals(21, $taxRate->toPercentage()->get());
    }

    public function test_it_accepts_decimal_rates_up_to_six_places(): void
    {
        foreach (['6.5', '12.25', '1.234567'] as $rate) {
            $this->assertSame($rate, VatPercentage::fromString($rate)->get());
        }
    }

    public function test_it_rejects_malformed_or_negative_rates(): void
    {
        foreach (['-1', ' 21', '21 ', '21.', '.5', 'abc'] as $rate) {
            try {
                VatPercentage::fromString($rate);
                $this->fail('Expected invalid VAT rate ['.$rate.'] to be rejected.');
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
