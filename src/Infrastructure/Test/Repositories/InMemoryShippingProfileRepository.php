<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\FindSuitableShippingProfile;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingCountry;
use Thinktomorrow\Trader\Domain\Model\Order\Price\SubTotal;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotFindShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;

final class InMemoryShippingProfileRepository implements ShippingProfileRepository, FindSuitableShippingProfile
{
    private static array $shippingProfiles = [];

    private string $nextReference = 'sss-123';

    public function save(ShippingProfile $shippingProfile): void
    {
        static::$shippingProfiles[$shippingProfile->shippingProfileId->get()] = $shippingProfile;
    }

    public function find(ShippingProfileId $shippingProfileId): ShippingProfile
    {
        if (! isset(static::$shippingProfiles[$shippingProfileId->get()])) {
            throw new CouldNotFindShippingProfile('No shipping found by id ' . $shippingProfileId);
        }

        return static::$shippingProfiles[$shippingProfileId->get()];
    }

    public function delete(ShippingProfileId $shippingProfileId): void
    {
        if (! isset(static::$shippingProfiles[$shippingProfileId->get()])) {
            throw new CouldNotFindShippingProfile('No available shipping found by id ' . $shippingProfileId);
        }

        unset(static::$shippingProfiles[$shippingProfileId->get()]);
    }

    public function nextReference(): ShippingProfileId
    {
        return ShippingProfileId::fromString($this->nextReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function clear()
    {
        static::$shippingProfiles = [];
    }

    public function findMatch(ShippingId $shippingId, SubTotal $subTotal, ShippingCountry $country, \DateTimeImmutable $date): ShippingProfile
    {
        // TODO: Implement findMatch() method.
    }
}
