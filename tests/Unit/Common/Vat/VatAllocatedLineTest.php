<?php

namespace Tests\Unit\Common\Vat;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

final class VatAllocatedLineTest extends TestCase
{
    public function test_it_exposes_taxable_base_vat_and_rate(): void
    {
        $line = new VatAllocatedLine(
            Money::EUR(10000),
            Money::EUR(2100),
            VatPercentage::fromString('21')
        );

        $this->assertEquals(Money::EUR(10000), $line->getTaxableBase());
        $this->assertEquals(Money::EUR(2100), $line->getVatAmount());
        $this->assertEquals(VatPercentage::fromString('21'), $line->getVatPercentage());
    }

    public function test_it_calculates_total_including_vat(): void
    {
        $line = new VatAllocatedLine(
            Money::EUR(333),
            Money::EUR(70),
            VatPercentage::fromString('21')
        );

        $this->assertEquals(
            Money::EUR(403),
            $line->getTotalIncludingVat()
        );
    }

    public function test_it_supports_zero_vat_amount(): void
    {
        $line = new VatAllocatedLine(
            Money::EUR(1000),
            Money::EUR(0),
            VatPercentage::fromString('0')
        );

        $this->assertEquals(Money::EUR(1000), $line->getTotalIncludingVat());
    }

    public function test_it_can_add_and_subtract_lines_with_the_same_rate(): void
    {
        $line = new VatAllocatedLine(Money::EUR(100), Money::EUR(21), VatPercentage::fromString('21'));
        $other = new VatAllocatedLine(Money::EUR(50), Money::EUR(11), VatPercentage::fromString('21'));

        $sum = $line->add($other);
        $difference = $line->subtract($other);

        $this->assertEquals(Money::EUR(150), $sum->getTaxableBase());
        $this->assertEquals(Money::EUR(32), $sum->getVatAmount());
        $this->assertEquals(Money::EUR(50), $difference->getTaxableBase());
        $this->assertEquals(Money::EUR(10), $difference->getVatAmount());
    }

    public function test_it_rejects_arithmetic_with_different_rates(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new VatAllocatedLine(Money::EUR(100), Money::EUR(21), VatPercentage::fromString('21')))
            ->add(new VatAllocatedLine(Money::EUR(100), Money::EUR(6), VatPercentage::fromString('6')));
    }
}
