<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test;

use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Price\SubTotal;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingCountry;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileRepository;
use Thinktomorrow\Trader\Application\RefreshCart\Adjusters\FindSuitableShippingProfile;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Exceptions\CouldNotFindShippingProfile;

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
        if(!isset(static::$shippingProfiles[$shippingProfileId->get()])) {
            throw new CouldNotFindShippingProfile('No shipping found by id ' . $shippingProfileId);
        }

        return static::$shippingProfiles[$shippingProfileId->get()];
    }

//    public function findMatch(ShippingId $shippingProfileId, SubTotal $subTotal, ShippingCountry $country, \DateTimeImmutable $date): ShippingRead
//    {
//        if(isset(static::$shippingProfiles[$shippingProfileId->get()])) {
//            $shipping = static::$shippingProfiles[$shippingProfileId->get()];
//
//            // FOr testing we just return the first rule as a match
//            /** @var Rule $rule */
//            foreach($shipping->getChildEntities()[Rule::class] as $rule) {
//                return ShippingRead::fromMappedData([
//                    'id' => $shippingProfileId->get(),
//                    'state' => ShippingState::initialized->value,
//                    'cost' => $rule->getCost()->getMoney()->getAmount(),
//                    'tax_rate' => $rule->getCost()->getTaxRate()->toPercentage()->get(),
//                    'includes_vat' => $rule->getCost()->includesTax(),
//                ], []);
//            }
//        }
//
//        throw new CouldNotFindShippingProfile();
//    }

    public function delete(ShippingId $shippingProfileId): void
    {
        if(!isset(static::$shippingProfiles[$shippingProfileId->get()])) {
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
