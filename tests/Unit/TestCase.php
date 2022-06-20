<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        DiscountTotal::setDiscountTaxRate(TaxRate::fromString('21'));
    }
}
