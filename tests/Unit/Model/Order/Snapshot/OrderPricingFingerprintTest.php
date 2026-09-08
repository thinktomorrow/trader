<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Order\Snapshot;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\PurchasableReference;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\Snapshot\OrderPricingFingerprint;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;

final class OrderPricingFingerprintTest extends TestCase
{
    public function test_order_exposes_the_calculated_pricing_fingerprint(): void
    {
        $order = $this->createOrder([
            $this->createLine('line-aaa', 100, 1),
        ]);

        $this->assertSame(OrderPricingFingerprint::calculate($order), $order->getPricingFingerprint());
        $this->assertStringStartsWith('pricing-v4:', $order->getPricingFingerprint());
    }

    public function test_component_order_does_not_affect_the_fingerprint(): void
    {
        $lineA = $this->createLine('line-aaa', 100, 1);
        $lineB = $this->createLine('line-bbb', 200, 2);

        $this->assertSame(
            $this->createOrder([$lineA, $lineB])->getPricingFingerprint(),
            $this->createOrder([$lineB, $lineA])->getPricingFingerprint(),
        );
    }

    public function test_pricing_input_changes_the_fingerprint(): void
    {
        $this->assertNotSame(
            $this->createOrder([$this->createLine('line-aaa', 100, 1)])->getPricingFingerprint(),
            $this->createOrder([$this->createLine('line-aaa', 100, 2)])->getPricingFingerprint(),
        );
    }

    /** @param Line[] $lines */
    private function createOrder(array $lines): Order
    {
        $order = Order::create(
            OrderId::fromString('order-aaa'),
            OrderReference::fromString('ORDER-AAA'),
            DefaultOrderState::confirmed,
        );

        foreach ($lines as $line) {
            $order->addOrUpdateLine($line);
        }

        return $order;
    }

    private function createLine(string $id, int $amount, int $quantity): Line
    {
        return Line::create(
            OrderId::fromString('order-aaa'),
            LineId::fromString($id),
            PurchasableReference::fromString('variant@'.$id),
            DefaultItemPrice::fromMoney(Money::EUR($amount), VatPercentage::fromString('21'), true),
            Quantity::fromInt($quantity),
            [],
        );
    }
}
