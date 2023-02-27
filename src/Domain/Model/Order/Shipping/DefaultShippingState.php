<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Shipping;

use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentDelivered;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentFailed;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentHaltedForPacking;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentInTransit;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentMarkedReadyForPacking;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentPacked;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingStates\ShipmentReturned;

enum DefaultShippingState: string implements ShippingState
{
    case none = "none"; // The order is still in customer hands (incomplete) and a shipment is not initialized yet.

    case ready_for_packing = 'ready_for_packing'; // ready to be packed by warehouse
    case halted_for_packing = 'halted_for_packing'; // something is wrong with the order (e.g. outdated order,  out of stock, ...)
    case packed = 'packed'; // ready for pickup by the logistic partner

    case in_transit = "in_transit"; // The package has been scanned by the carrier and is in transit.
    case delivered = "delivered"; // The package has been successfully delivered.
    case returned = "returned"; // The package is en route to be returned to the sender, or has been returned successfully.
    case failed = "failed"; // The carrier indicated that there has been an issue with the delivery. This can happen for various reasons and depends on the carrier. This status does not indicate a technical, but a delivery issue.
    case unknown = "unknown"; // unknown status or the package has not been found via the carrier’s tracking system.

    public static function fromString(string $state): self
    {
        return static::from($state);
    }

    public function getValueAsString(): string
    {
        return $this->value;
    }

    public function equals($other): bool
    {
        return (get_class($this) === get_class($other) && $this->getValueAsString() === $other->getValueAsString());
    }

    public static function getDefaultState(): self
    {
        return static::none;
    }

    public static function getStates(): array
    {
        return static::cases();
    }

    public static function getTransitions(): array
    {
        return [
            'start_packing' => [
                'from' => [self::none, self::halted_for_packing],
                'to' => self::ready_for_packing,
            ],
            'halt_packing' => [
                'from' => [self::none, self::ready_for_packing],
                'to' => self::halted_for_packing,
            ],
            'pack' => [
                'from' => [self::ready_for_packing],
                'to' => self::packed,
            ],
            'ship' => [
                'from' => [self::packed],
                'to' => self::in_transit,
            ],
            'deliver' => [
                'from' => [self::packed, self::in_transit],
                'to' => self::delivered,
            ],
            'return' => [
                'from' => [self::delivered, self::in_transit],
                'to' => self::returned,
            ],
        ];
    }

    public static function getEventMapping(): array
    {
        return [
            self::ready_for_packing->value => ShipmentMarkedReadyForPacking::class,
            self::halted_for_packing->value => ShipmentHaltedForPacking::class,
            self::packed->value => ShipmentPacked::class,
            self::in_transit->value => ShipmentInTransit::class,
            self::delivered->value => ShipmentDelivered::class,
            self::returned->value => ShipmentReturned::class,
            self::failed->value => ShipmentFailed::class,
        ];
    }
}
