<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountPriceDefaults;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        DiscountPriceDefaults::setDiscountTaxRate(VatPercentage::fromString('21'));
        DiscountPriceDefaults::setDiscountIncludeTax(true);
    }
}
