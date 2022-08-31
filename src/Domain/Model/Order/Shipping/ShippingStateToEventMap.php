<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Shipping;

use Thinktomorrow\Trader\Domain\Common\Map\HasSimpleMapping;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentPacked;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShippingFailed;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentReturned;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentInTransit;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentDelivered;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentHaltedForPacking;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentMarkedReadyForPacking;

class ShippingStateToEventMap
{
    use HasSimpleMapping;

    public static function getDefaultMapping(): array
    {
        return [
            ShippingState::ready_for_packing->value => ShipmentMarkedReadyForPacking::class,
            ShippingState::halted_for_packing->value => ShipmentHaltedForPacking::class,
            ShippingState::packed->value => ShipmentPacked::class,
            ShippingState::in_transit->value => ShipmentInTransit::class,
            ShippingState::delivered->value => ShipmentDelivered::class,
            ShippingState::returned->value => ShipmentReturned::class,
            ShippingState::failed->value => ShippingFailed::class,
        ];
    }
}
