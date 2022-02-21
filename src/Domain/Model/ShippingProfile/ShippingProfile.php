<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\ShippingProfile;

use Money\Money;
use Thinktomorrow\Trader\Domain\Common\Cash\Price;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCountry;

final class ShippingProfile implements Aggregate
{
    use RecordsEvents;

    public readonly ShippingProfileId $shippingProfileId;
    private array $tariffs = [];
    private array $countries = [];

    public static function create(ShippingProfileId $shippingProfileId): static
    {
        $shipping = new static();
        $shipping->shippingProfileId = $shippingProfileId;

        return $shipping;
    }

    public function findTariffByPrice(Price $price, bool $tariff_amounts_include_tax): ?Tariff
    {
        $normalizedAmount = $tariff_amounts_include_tax ? $price->getIncludingVat() : $price->getExcludingVat();

        /** @var Tariff $tariff */
        foreach ($this->tariffs as $tariff) {
            if($tariff->withinRange($normalizedAmount)) {
                return $tariff;
            }
        }

        return null;
    }

    public function addTariff(TariffNumber $tariffNumber, Money $tariff, Money $from, Money $to): void
    {
        $this->tariffs[] = Tariff::create($this->shippingProfileId, $tariffNumber, $tariff, $from, $to);
    }

    public function updateTariff(TariffNumber $tariffNumber, Money $tariff, Money $from, Money $to): void
    {
        if (null !== $tariffIndexToBeUpdated = $this->findTariffIndex($tariffNumber)) {
            $this->tariffs[$tariffIndexToBeUpdated]->update($tariff, $from, $to);
        }
    }

    public function deleteTariff(TariffNumber $tariffNumber): void
    {
        if (null !== $tariffIndexToBeDeleted = $this->findTariffIndex($tariffNumber)) {
            $tariffToBeDeleted = $this->tariffs[$tariffIndexToBeDeleted];

            unset($this->tariffs[$tariffIndexToBeDeleted]);
        }
    }

    public function addCountry(ShippingCountry $country): void
    {
        $this->countries[] = $country;
    }

    public function deleteCountry(ShippingCountry $country): void
    {
        /** @var \Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCountry $existingCountry */
        foreach($this->countries as $index => $existingCountry)
        {
            if($country->equals($existingCountry)) {
                unset($this->countries[$index]);
            }
        }
    }

    public function getMappedData(): array
    {
        return [
            'shipping_profile_id' => $this->shippingProfileId->get(),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Tariff::class => $this->tariffs,
            ShippingCountry::class => $this->countries,
        ];
    }


    public function hasCountry(ShippingCountry $country): bool
    {
        /** @var \Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCountry $existingCountry */
        foreach($this->countries as $existingCountry) {
            if($existingCountry->equals($country)) {
                return true;
            }
        }

        return false;
    }

//    public function getCountries(): array
//    {
//        return $this->countries;
//    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $shipping = new static();
        $shipping->shippingProfileId = ShippingProfileId::fromString($state['shipping_profile_id']);

        $shipping->tariffs = array_map(fn($tariffState) => Tariff::fromMappedData($tariffState, $state), $childEntities[Tariff::class]);
        $shipping->countries = array_map(fn($countryKey) => ShippingCountry::fromString($countryKey), $childEntities[ShippingCountry::class]);

        return $shipping;
    }

    private function findTariffIndex(TariffNumber $tariffNumber): ?int
    {
        foreach ($this->tariffs as $index => $tariff) {
            if ($tariffNumber->asInt() === $tariff->tariffNumber->asInt()) {
                return $index;
            }
        }

        return null;
    }
}
