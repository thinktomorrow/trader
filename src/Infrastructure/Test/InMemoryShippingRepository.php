<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Domain\Model\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingRepository;
use Thinktomorrow\Trader\Domain\Model\Shipping\Exceptions\CouldNotFindShipping;

final class InMemoryShippingRepository implements ShippingRepository
{
    private static array $shippings = [];

    private string $nextReference = 'shipping-123';

    public function save(Shipping $shipping): void
    {
        static::$shippings[$shipping->shippingId->get()] = $shipping;
    }

    public function find(ShippingId $shippingId): Shipping
    {
        if(!isset(static::$shippings[$shippingId->get()])) {
            throw new CouldNotFindShipping('No shipping found by id ' . $shippingId);
        }

        return static::$shippings[$shippingId->get()];
    }

    public function delete(ShippingId $shippingId): void
    {
        if(!isset(static::$shippings[$shippingId->get()])) {
            throw new CouldNotFindShipping('No available shipping found by id ' . $shippingId);
        }

        unset(static::$shippings[$shippingId->get()]);
    }

    public function nextReference(): ShippingId
    {
        return ShippingId::fromString($this->nextReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function clear()
    {
        static::$shippings = [];
    }
}
