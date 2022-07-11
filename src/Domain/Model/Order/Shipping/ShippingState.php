<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Shipping;

enum ShippingState: string
{
    case none = "none"; // The order is still in customer hands (incomplete) and a shipment is not initialized yet.

    case halted_for_packing = 'halted_for_packing'; // something is wrong with the order (e.g. outdated order,  out of stock, ...)
    case ready_for_packing = 'ready_for_packing'; // ready to be packed by warehouse
    case packed = 'packed'; // ready for pickup by the logistic partner

    case label_created = "label_created"; // The label is created but before the package is dropped off or picked up by the carrier.
    case transit = "transit"; // The package has been scanned by the carrier and is in transit.
    case delivered = "delivered"; // The package has been successfully delivered.
    case returned = "returned"; // The package is en route to be returned to the sender, or has been returned successfully.
    case failure = "failure"; // The carrier indicated that there has been an issue with the delivery. This can happen for various reasons and depends on the carrier. This status does not indicate a technical, but a delivery issue.
    case unknown = "unknown"; // unknown status or the package has not been found via the carrier’s tracking system.
}
