<?php

declare(strict_types=1);

namespace Tests\Unit\Common\Vat;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\Exceptions\InvalidVatAllocatedTotal;
use Thinktomorrow\Trader\Domain\Common\Vat\Exceptions\InvalidVatAllocatedTotalReason;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedTotalPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

final class VatAllocatedTotalPriceTest extends TestCase
{
    public function test_it_exposes_totals(): void
    {
        $total = new VatAllocatedTotalPrice(
            [new VatAllocatedLine(Money::EUR(15000), Money::EUR(2550), VatPercentage::fromString('17'))],
            Money::EUR(15000),
            Money::EUR(2550),
            Money::EUR(17550)
        );

        $this->assertEquals(Money::EUR(15000), $total->getTotalExcludingVat());
        $this->assertEquals(Money::EUR(2550), $total->getTotalVat());
        $this->assertEquals(Money::EUR(17550), $total->getTotalIncludingVat());
    }

    public function test_it_returns_vat_lines(): void
    {
        $lines = [
            new VatAllocatedLine(
                Money::EUR(10000),
                Money::EUR(2100),
                VatPercentage::fromString('21')
            ),
            new VatAllocatedLine(
                Money::EUR(5000),
                Money::EUR(300),
                VatPercentage::fromString('6')
            ),
        ];

        $total = new VatAllocatedTotalPrice(
            $lines,
            Money::EUR(15000),
            Money::EUR(2400),
            Money::EUR(17400)
        );

        $this->assertCount(2, $total->getVatLines());
        $this->assertSame($lines, $total->getVatLines());
    }

    public function test_it_can_find_vat_line_by_rate(): void
    {
        $line21 = new VatAllocatedLine(
            Money::EUR(10000),
            Money::EUR(2100),
            VatPercentage::fromString('21')
        );

        $line6 = new VatAllocatedLine(
            Money::EUR(5000),
            Money::EUR(300),
            VatPercentage::fromString('6')
        );

        $total = new VatAllocatedTotalPrice(
            [$line21, $line6],
            Money::EUR(15000),
            Money::EUR(2400),
            Money::EUR(17400)
        );

        $this->assertSame($line21, $total->findByRate('21'));
        $this->assertSame($line6, $total->findByRate('6'));
        $this->assertNull($total->findByRate('12'));
    }

    public function test_it_rejects_invalid_vat_lines(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new VatAllocatedTotalPrice(
            ['invalid'],
            Money::EUR(0),
            Money::EUR(0),
            Money::EUR(0)
        );
    }

    public function test_totals_are_consistent(): void
    {
        $lines = [
            new VatAllocatedLine(
                Money::EUR(333),
                Money::EUR(70),
                VatPercentage::fromString('21')
            ),
            new VatAllocatedLine(
                Money::EUR(667),
                Money::EUR(40),
                VatPercentage::fromString('6')
            ),
        ];

        $totalExcl = Money::EUR(1000);
        $totalVat = Money::EUR(110);
        $totalIncl = Money::EUR(1110);

        $total = new VatAllocatedTotalPrice(
            $lines,
            $totalExcl,
            $totalVat,
            $totalIncl
        );

        $this->assertEquals(
            $total->getTotalIncludingVat(),
            $total->getTotalExcludingVat()->add($total->getTotalVat())
        );
    }

    public function test_it_rejects_totals_that_do_not_match_vat_lines(): void
    {
        try {
            new VatAllocatedTotalPrice(
                [new VatAllocatedLine(Money::EUR(100), Money::EUR(21), VatPercentage::fromString('21'))],
                Money::EUR(100),
                Money::EUR(20),
                Money::EUR(120),
            );
            $this->fail('An inconsistent VAT line amount should be rejected.');
        } catch (InvalidVatAllocatedTotal $exception) {
            $this->assertSame(InvalidVatAllocatedTotalReason::VatAmountSumMismatch, $exception->reason());
            $this->assertSame('1', $exception->context()['deltas']['vat_amount']);
            $this->assertSame('21', $exception->context()['vat_lines'][0]['vat_percentage']);
        }
    }

    public function test_it_rejects_an_inconsistent_total_equation(): void
    {
        try {
            new VatAllocatedTotalPrice(
                [new VatAllocatedLine(Money::EUR(100), Money::EUR(21), VatPercentage::fromString('21'))],
                Money::EUR(100),
                Money::EUR(21),
                Money::EUR(120),
            );
            $this->fail('An inconsistent total equation should be rejected.');
        } catch (InvalidVatAllocatedTotal $exception) {
            $this->assertSame(InvalidVatAllocatedTotalReason::TotalEquationMismatch, $exception->reason());
            $this->assertSame('1', $exception->context()['deltas']['total_equation']);
            $this->assertSame('EUR', $exception->context()['currency']);
        }
    }

    public function test_it_rejects_taxable_bases_that_do_not_match_total_excluding_vat(): void
    {
        try {
            new VatAllocatedTotalPrice(
                [new VatAllocatedLine(Money::EUR(99), Money::EUR(21), VatPercentage::fromString('21'))],
                Money::EUR(100),
                Money::EUR(21),
                Money::EUR(121),
            );
            $this->fail('An inconsistent taxable base should be rejected.');
        } catch (InvalidVatAllocatedTotal $exception) {
            $this->assertSame(InvalidVatAllocatedTotalReason::TaxableBaseSumMismatch, $exception->reason());
            $this->assertSame('-1', $exception->context()['deltas']['taxable_base']);
            $this->assertSame('99', $exception->context()['calculated_totals']['vat_line_taxable_base_sum']);
        }
    }

    public function test_it_can_add_operational_context_without_losing_diagnostics(): void
    {
        try {
            new VatAllocatedTotalPrice(
                [new VatAllocatedLine(Money::EUR(100), Money::EUR(21), VatPercentage::fromString('21'))],
                Money::EUR(100),
                Money::EUR(20),
                Money::EUR(120),
            );
            $this->fail('An inconsistent VAT line amount should be rejected.');
        } catch (InvalidVatAllocatedTotal $exception) {
            $enriched = $exception->withContext([
                'allocation_stage' => 'combined_order',
                'reason' => 'overridden',
            ]);

            $this->assertSame('combined_order', $enriched->context()['allocation_stage']);
            $this->assertSame(InvalidVatAllocatedTotalReason::VatAmountSumMismatch->value, $enriched->context()['reason']);
            $this->assertSame('1', $enriched->context()['deltas']['vat_amount']);
            $this->assertSame($exception, $enriched->getPrevious());
        }
    }
}
