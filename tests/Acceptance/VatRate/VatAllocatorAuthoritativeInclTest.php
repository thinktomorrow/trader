<?php

namespace Tests\Acceptance\VatRate;

use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\VatRate\Allocator\ProRateAllocator;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

final class VatAllocatorAuthoritativeInclTest extends TestCase
{
    #[DataProvider('authoritativeItemProvider')]
    public function test_authoritative_item_including_vat_is_preserved(int $unitIncl, int $quantity, string $vatRate): void
    {
        $vatPercentage = VatPercentage::fromString($vatRate);

        // Item with authoritative incl VAT
        $itemPrice = DefaultItemPrice::fromMoney(
            Money::EUR($unitIncl),
            $vatPercentage,
            true // ← authoritative incl
        );

        $line = $this->orderContext->createLine(
            'order-aaa',
            'line-aaa',
            [
                'unit_price_incl' => $unitIncl,
                'tax_rate' => $vatRate,
                'includes_vat' => true,
                'quantity' => $quantity,
            ]
        );

        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $line);

        $allocator = new VatAllocator(new ProRateAllocator());

        $result = $allocator->allocate(
            $order,
            Cash::zero(),
            Cash::zero(),
            Cash::zero()
        )->items();

        $expectedIncl = $unitIncl * $quantity;

        $this->assertEquals(
            Money::EUR($expectedIncl),
            $result->getTotalIncludingVat(),
            'Authoritative including VAT must be preserved'
        );

        // Sanity check: excl + vat = incl
        $this->assertEquals(
            $result->getTotalIncludingVat(),
            $result->getTotalExcludingVat()->add($result->getTotalVat())
        );
    }

    public static function authoritativeItemProvider(): array
    {
        return [
            'simple quantity' => [1000, 2, '21'],     // €10 × 2 = €20
            'rounding edge' => [2150, 9999, '21'],    // your reported case
            'low price high qty' => [99, 123, '21'],
            '6 percent vat' => [1060, 7, '6'],
            'single item' => [1999, 1, '21'],
        ];
    }
}
