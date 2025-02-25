<?php

namespace Tests\Unit\Common\Taxes;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;

class TaxRateTest extends TestCase
{
    public function test_it_can_be_set_by_integer()
    {
        $taxRate = VatPercentage::fromString('21');

        $this->assertEquals(0.21, $taxRate->toPercentage()->toDecimal());
        $this->assertEquals(21, $taxRate->toPercentage()->get());
    }
}
