<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\ShippingProfile;

interface ShippingProfileRepository
{
    public function save(ShippingProfile $shippingProfile): void;

    public function find(ShippingProfileId $shippingProfileId): ShippingProfile;

    public function delete(ShippingProfileId $shippingProfileId): void;

    public function nextReference(): ShippingProfileId;

    public function nextTariffReference(): TariffId;
}
