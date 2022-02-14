<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Shipping;

use Thinktomorrow\Trader\Domain\Common\State\StateValue;

enum ShippingState: string
{
    case initialized = "initialized"; // The label is created but before the package is dropped off or picked up by the carrier.
    case transit = "transit"; // The package has been scanned by the carrier and is in transit.
    case delivered = "delivered"; // The package has been successfully delivered.
    case returned = "returned"; // The package is en route to be returned to the sender, or has been returned successfully.
    case failure = "failure"; // The carrier indicated that there has been an issue with the delivery. This can happen for various reasons and depends on the carrier. This status does not indicate a technical, but a delivery issue.
    case unknown = "unknown"; // unknown status or the package has not been found via the carrier’s tracking system.
}
