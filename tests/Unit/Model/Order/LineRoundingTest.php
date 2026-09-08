<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultItemPrice;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\PurchasableReference;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantUnitPrice;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountId as PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

final class LineRoundingTest extends TestCase
{
    public function test_it_rounds_vat_on_the_full_line_amount(): void
    {
        $line = Line::create(
            OrderId::fromString('order-aaa'),
            LineId::fromString('line-aaa'),
            PurchasableReference::fromString('variant@variant-aaa'),
            VariantUnitPrice::fromMoney(Money::EUR(199), VatPercentage::fromString('21'), true),
            Quantity::fromInt(3),
            [],
        );

        $this->assertInstanceOf(VariantUnitPrice::class, $line->getSubtotal());
        $this->assertEquals(Money::EUR(597), $line->getTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(493), $line->getTotal()->getExcludingVat());
        $this->assertEquals(Money::EUR(104), $line->getTotal()->getVatTotal());
    }

    public function test_total_subtracts_separately_rounded_discount_component(): void
    {
        $line = $this->discountedLine();

        $this->assertEquals(Money::EUR(493), $line->getSubtotal()->getExcludingVat());
        $this->assertEquals(Money::EUR(597), $line->getSubtotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(15), $line->getDiscountPrice()->getExcludingVat());
        $this->assertEquals(Money::EUR(18), $line->getDiscountPrice()->getIncludingVat());
        $this->assertEquals(Money::EUR(478), $line->getTotal()->getExcludingVat());
        $this->assertEquals(Money::EUR(101), $line->getTotal()->getVatTotal());
        $this->assertEquals(Money::EUR(579), $line->getTotal()->getIncludingVat());
    }

    private function discountedLine(): Line
    {
        $line = Line::create(
            OrderId::fromString('order-aaa'),
            LineId::fromString('line-aaa'),
            PurchasableReference::fromString('variant@variant-aaa'),
            DefaultItemPrice::fromMoney(Money::EUR(199), VatPercentage::fromString('21'), true),
            Quantity::fromInt(3),
            [],
        );
        $line->addDiscount(Discount::create(
            OrderId::fromString('order-aaa'),
            DiscountId::fromString('discount-aaa'),
            DiscountableType::line,
            $line->getDiscountableId(),
            PromoId::fromString('promo-aaa'),
            PromoDiscountId::fromString('promo-discount-aaa'),
            DefaultItemDiscountPrice::fromIncludingVat(Money::EUR(6), VatPercentage::fromString('21')),
            [],
        ));

        return $line;
    }
}
