<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Order\Snapshot;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\VatAllocatedLine;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Snapshot\OrderPricingSnapshot;

final class OrderPricingSnapshotTest extends TestCase
{
    public function test_it_rejects_an_inconsistent_total_equation(): void
    {
        $this->expectException(\LogicException::class);

        $this->snapshot(Money::EUR(100), Money::EUR(21), Money::EUR(120), [
            new VatAllocatedLine(Money::EUR(100), Money::EUR(21), VatPercentage::fromString('21')),
        ]);
    }

    public function test_it_rejects_a_vat_line_sum_that_differs_from_total_vat(): void
    {
        $this->expectException(\LogicException::class);

        $this->snapshot(Money::EUR(100), Money::EUR(21), Money::EUR(121), [
            new VatAllocatedLine(Money::EUR(100), Money::EUR(20), VatPercentage::fromString('21')),
        ]);
    }

    public function test_it_rejects_a_taxable_base_sum_that_differs_from_total_excluding_vat(): void
    {
        $this->expectException(\LogicException::class);

        $this->snapshot(Money::EUR(100), Money::EUR(21), Money::EUR(121), [
            new VatAllocatedLine(Money::EUR(99), Money::EUR(21), VatPercentage::fromString('21')),
        ]);
    }

    public function test_it_rejects_invalid_vat_line_elements(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->snapshot(Money::EUR(0), Money::EUR(0), Money::EUR(0), ['invalid']);
    }

    private function snapshot(Money $totalExcl, Money $totalVat, Money $totalIncl, array $vatLines): OrderPricingSnapshot
    {
        return OrderPricingSnapshot::fromVatAllocation(
            vatLines: $vatLines,
            subtotalExcl: $totalExcl,
            subtotalIncl: $totalIncl,
            shippingExcl: Money::EUR(0),
            shippingIncl: Money::EUR(0),
            paymentExcl: Money::EUR(0),
            paymentIncl: Money::EUR(0),
            discountExcl: Money::EUR(0),
            discountIncl: Money::EUR(0),
            totalExcl: $totalExcl,
            totalVat: $totalVat,
            totalIncl: $totalIncl,
            pricingFingerprint: 'fingerprint',
        );
    }
}
