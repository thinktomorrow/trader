<?php

namespace Tests\Acceptance\VatRate;

use Money\Money;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\VatRate\Allocator\ProRateAllocator;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

final class VatAllocatorTest extends TestCase
{
    private VatAllocator $allocator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->allocator = new VatAllocator(new ProRateAllocator());
    }

    public function test_it_allocates_items_only_single_vat_rate(): void
    {
        $order = $this->orderWithLines([
            $this->line(1000, 2, '21'), // 20.00 excl
            $this->line(500, 1, '21'),  // 5.00 excl
        ]);

        $result = $this->allocator->allocate(
            $order,
            Money::EUR(0),
            Money::EUR(0),
            Money::EUR(0),
        );

        $items = $result->items();

        $this->assertEquals(Money::EUR(2500), $items->getTotalExcludingVat());
        $this->assertEquals(Money::EUR(525), $items->getTotalVat());
        $this->assertEquals(Money::EUR(3025), $items->getTotalIncludingVat());
        $this->assertCount(1, $items->getVatLines());
    }

    public function test_it_allocates_items_across_multiple_vat_rates(): void
    {
        $order = $this->orderWithLines([
            $this->line(1000, 1, '21'), // 10.00 excl
            $this->line(1000, 1, '6'),  // 10.00 excl
        ]);

        $result = $this->allocator->allocate(
            $order,
            Money::EUR(0),
            Money::EUR(0),
            Money::EUR(0),
        );

        $items = $result->items();

        $this->assertEquals(Money::EUR(2000), $items->getTotalExcludingVat());
        $this->assertEquals(
            Money::EUR(210 + 60),
            $items->getTotalVat()
        );
        $this->assertEquals(
            $items->getTotalExcludingVat()->add($items->getTotalVat()),
            $items->getTotalIncludingVat()
        );
    }

    public function test_shipping_is_allocated_pro_rata_over_vat_rates(): void
    {
        $order = $this->orderWithLines([
            $this->line(2000, 1, '21'), // 2/3
            $this->line(1000, 1, '6'),  // 1/3
        ]);

        $result = $this->allocator->allocate(
            $order,
            Money::EUR(300), // shipping excl
            Money::EUR(0),
            Money::EUR(0),
        );

        $shipping = $result->shipping();

        $this->assertEquals(Money::EUR(200), $shipping->findByRate('21')->getTaxableBase());
        $this->assertEquals(Money::EUR(100), $shipping->findByRate('6')->getTaxableBase());

        $this->assertEquals(
            $shipping->getTotalExcludingVat()->add($shipping->getTotalVat()),
            $shipping->getTotalIncludingVat()
        );
    }

    public function test_discount_is_allocated_pro_rata_and_subtracted(): void
    {
        $order = $this->orderWithLines([
            $this->line(1000, 1, '21'),
            $this->line(1000, 1, '6'),
        ]);

        $result = $this->allocator->allocate(
            $order,
            Money::EUR(0),
            Money::EUR(0),
            Money::EUR(300),
        );

        $discount = $result->discounts();

        $this->assertEquals(Money::EUR(150), $discount->findByRate('21')->getTaxableBase());
        $this->assertEquals(Money::EUR(150), $discount->findByRate('6')->getTaxableBase());
    }

    public function test_total_is_consistent_with_items_services_and_discounts(): void
    {
        $order = $this->orderWithLines([
            $this->line(1000, 2, '21'), // 20.00
            $this->line(500, 2, '6'),   // 10.00
        ]);

        $result = $this->allocator->allocate(
            $order,
            Money::EUR(600),  // shipping
            Money::EUR(400),  // payment
            Money::EUR(500),  // discount
        );

        $total = $result->total();

        $this->assertEquals(
            $total->getTotalExcludingVat()->add($total->getTotalVat()),
            $total->getTotalIncludingVat()
        );
    }

    private function orderWithLines(array $lines): Order
    {
        $order = $this->orderContext->createEmptyOrder();

        foreach ($lines as $line) {
            $this->orderContext->addLineToOrder($order, $line);
        }

        return $order;
    }

    private function line(int $unitExcl, int $qty, string $vat): Line
    {
        return $this->orderContext->createLine(
            'order-aaa', uniqid('line-', true), [
                'unit_price_excl' => $unitExcl,
                'tax_rate' => $vat,
                'includes_vat' => false,
                'quantity' => $qty,
            ]
        );
    }
}

