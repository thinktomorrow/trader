<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\PurchasableReference;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

final class LineVatRoundingStrategyTest extends TestCase
{
    public function test_it_uses_line_based_rounding_by_default(): void
    {
        $line = Line::create(
            OrderId::fromString('order-aaa'),
            LineId::fromString('line-aaa'),
            PurchasableReference::fromString('variant@variant-aaa'),
            DefaultItemPrice::fromMoney(Money::EUR(199), VatPercentage::fromString('21'), true),
            Quantity::fromInt(3),
            [],
        );

        $this->assertEquals(Money::EUR(597), $line->getTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(493), $line->getTotal()->getExcludingVat());
        $this->assertEquals(Money::EUR(104), $line->getTotal()->getVatTotal());
    }

    public function test_it_uses_unit_based_rounding_when_configured_on_line_data(): void
    {
        $line = Line::create(
            OrderId::fromString('order-aaa'),
            LineId::fromString('line-aaa'),
            PurchasableReference::fromString('variant@variant-aaa'),
            DefaultItemPrice::fromMoney(Money::EUR(199), VatPercentage::fromString('21'), true),
            Quantity::fromInt(3),
            ['vat_rounding_strategy' => 'unit_based'],
        );

        $this->assertEquals(Money::EUR(597), $line->getTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(492), $line->getTotal()->getExcludingVat());
        $this->assertEquals(Money::EUR(105), $line->getTotal()->getVatTotal());
    }
}
