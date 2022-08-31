<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\State\OrderStateToEventMap;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentStateToEventMap;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingStateToEventMap;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use TestHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        DiscountTotal::setDiscountTaxRate(TaxRate::fromString('21'));

        OrderStateToEventMap::set(OrderStateToEventMap::getDefaultMapping());
        PaymentStateToEventMap::set(PaymentStateToEventMap::getDefaultMapping());
        ShippingStateToEventMap::set(ShippingStateToEventMap::getDefaultMapping());
    }
}
