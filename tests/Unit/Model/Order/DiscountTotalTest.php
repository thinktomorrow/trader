<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountPriceDefaults;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;

class DiscountTotalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DiscountPriceDefaults::setDiscountTaxRate(VatPercentage::fromString('10'));
        DiscountPriceDefaults::setDiscountIncludeTax(true);
    }

    protected function tearDown(): void
    {
        DiscountPriceDefaults::clear();

        parent::tearDown();
    }

    public function test_it_can_create_total()
    {
        $total = DiscountTotal::fromDefault(Money::EUR(50));

        $this->assertEquals(Money::EUR(50), $total->getIncludingVat());
        $this->assertEquals(Money::EUR(45), $total->getExcludingVat());
    }

    public function test_it_can_create_zero_total()
    {
        $total = DiscountTotal::zero();

        $this->assertEquals(Money::EUR(0), $total->getIncludingVat());
        $this->assertEquals(Money::EUR(0), $total->getExcludingVat());
    }

    public function test_it_fails_when_default_discount_taxrate_is_not_given()
    {
        $this->expectException(\DomainException::class);

        DiscountPriceDefaults::clear();
        DiscountPriceDefaults::setDiscountIncludeTax(true);

        DiscountTotal::fromDefault(Money::EUR(50));
    }

    public function test_it_fails_when_default_includes_vat_is_not_given()
    {
        $this->expectException(\DomainException::class);

        DiscountPriceDefaults::clear();
        DiscountPriceDefaults::setDiscountTaxRate(VatPercentage::fromString('10'));

        DiscountTotal::fromDefault(Money::EUR(50));
    }
}
