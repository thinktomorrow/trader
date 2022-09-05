<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountPriceDefaults;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateToEventMap;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateToEventMap;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateToEventMap;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        DiscountPriceDefaults::setDiscountTaxRate(TaxRate::fromString('21'));
        DiscountPriceDefaults::setDiscountIncludeTax(true);

        OrderStateToEventMap::set(OrderStateToEventMap::getDefaultMapping());
        PaymentStateToEventMap::set(PaymentStateToEventMap::getDefaultMapping());
        ShippingStateToEventMap::set(ShippingStateToEventMap::getDefaultMapping());
    }
}
