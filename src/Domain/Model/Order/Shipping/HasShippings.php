<?php

namespace Thinktomorrow\Trader\Domain\Model\Order\Shipping;

use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\CouldNotFindShippingOnOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Exceptions\ShippingAlreadyOnOrder;

trait HasShippings
{
    /** @var Shipping[] */
    private array $shippings = [];

    public function addShipping(Shipping $shipping): void
    {
        if (null !== $this->findShippingIndex($shipping->shippingId)) {
            throw new ShippingAlreadyOnOrder(
                'Cannot add shipping because order ['.$this->orderId->get().'] already has shipping ['.$shipping->shippingId->get().']'
            );
        }

        $this->shippings[] = $shipping;

        $this->recordEvent(new ShippingAdded($this->orderId, $shipping->shippingId));
    }

    public function updateShipping(Shipping $shipping): void
    {
        if (null === $shippingIndex = $this->findShippingIndex($shipping->shippingId)) {
            throw new CouldNotFindShippingOnOrder(
                'Cannot update shipping because order ['.$this->orderId->get().'] has no shipping by id ['.$shipping->shippingId->get().']'
            );
        }

        $this->shippings[$shippingIndex] = $shipping;

        $this->recordEvent(new ShippingUpdated($this->orderId, $shipping->shippingId));
    }

    public function deleteShipping(ShippingId $shippingId): void
    {
        if (null !== $shippingIndex = $this->findShippingIndex($shippingId)) {
            unset($this->shippings[$shippingIndex]);

            $this->recordEvent(new ShippingDeleted($this->orderId, $shippingId));
        }
    }

    public function findShipping(ShippingId $shippingId): Shipping
    {
        if (null === $shippingIndex = $this->findShippingIndex($shippingId)) {
            throw new CouldNotFindShippingOnOrder(
                'Cannot update shipping because order ['.$this->orderId->get().'] has no shipping by id ['.$shippingId->get().']'
            );
        }

        return $this->shippings[$shippingIndex];
    }

    private function findShippingIndex(ShippingId $shippingId): ?int
    {
        foreach ($this->shippings as $index => $shipping) {
            if ($shippingId->equals($shipping->shippingId)) {
                return $index;
            }
        }

        return null;
    }
}
