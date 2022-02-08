<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Order\Domain;

use Thinktomorrow\Trader\Common\State\StateValueDefaults;

class ShippingState
{
    use StateValueDefaults;

    /* @var string identify the state key */
    public static string $KEY = 'shipping_state';

    const INITIALIZED = "initialized"; // The label is created but before the package is dropped off or picked up by the carrier.
    const TRANSIT = "transit"; // The package has been scanned by the carrier and is in transit.
    const DELIVERED = "delivered"; // The package has been successfully delivered.
    const RETURNED = "returned"; // The package is en route to be returned to the sender, or has been returned successfully.
    const FAILURE = "failure"; // The carrier indicated that there has been an issue with the delivery. This can happen for various reasons and depends on the carrier. This status does not indicate a technical, but a delivery issue.
    const UNKNOWN = "unknown"; // unknown status or the package has not been found via the carrier’s tracking system.
}
