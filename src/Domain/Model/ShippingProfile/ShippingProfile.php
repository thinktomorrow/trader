<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\ShippingProfile;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Common\Price\Price;
use Thinktomorrow\Trader\Domain\Common\Price\PriceTotal;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Country\HasCountryIds;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\Events\TariffDeleted;

final class ShippingProfile implements Aggregate
{
    use RecordsEvents;
    use HasCountryIds;
    use HasData;

    public readonly ShippingProfileId $shippingProfileId;
    private ShippingProfileState $state;

    /** @var Tariff[] */
    private array $tariffs = [];

    public static function create(ShippingProfileId $shippingProfileId): static
    {
        $shipping = new static();
        $shipping->shippingProfileId = $shippingProfileId;
        $shipping->state = ShippingProfileState::online;

        return $shipping;
    }

    public function updateState(ShippingProfileState $state): void
    {
        $this->state = $state;
    }

    public function getState(): ShippingProfileState
    {
        return $this->state;
    }

    public function getTariffs(): array
    {
        return $this->tariffs;
    }

    public function findTariffByPrice(Price|PriceTotal $price, bool $tariff_amounts_include_tax): ?Tariff
    {
        $normalizedAmount = $tariff_amounts_include_tax ? $price->getIncludingVat() : $price->getExcludingVat();

        foreach ($this->tariffs as $tariff) {
            if ($tariff->withinRange($normalizedAmount)) {
                return $tariff;
            }
        }

        return null;
    }

    public function findTariff(TariffId $tariffId): Tariff
    {
        foreach ($this->tariffs as $tariff) {
            if ($tariff->tariffId->equals($tariffId)) {
                return $tariff;
            }
        }

        throw new \InvalidArgumentException('No Tariff found by id ' . $tariffId->get());
    }

    public function addTariff(Tariff $tariff): void
    {
        $this->tariffs[] = $tariff;
    }

    public function deleteTariff(TariffId $tariffId): void
    {
        foreach ($this->tariffs as $i => $tariff) {
            if ($tariff->tariffId->equals($tariffId)) {
                unset($this->tariffs[$i]);
                $this->recordEvent(new TariffDeleted($this->shippingProfileId, $tariffId));
            }
        }
    }

    public function getMappedData(): array
    {
        return [
            'shipping_profile_id' => $this->shippingProfileId->get(),
            'state' => $this->state->value,
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Tariff::class => array_map(fn (Tariff $tariff) => $tariff->getMappedData(), $this->tariffs),
            CountryId::class => array_map(fn (CountryId $countryId) => $countryId->get(), $this->countryIds),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $shipping = new static();
        $shipping->shippingProfileId = ShippingProfileId::fromString($state['shipping_profile_id']);
        $shipping->state = ShippingProfileState::from($state['state']);
        $shipping->data = json_decode($state['data'], true);

        $shipping->tariffs = array_map(fn ($tariffState) => Tariff::fromMappedData($tariffState, $state), $childEntities[Tariff::class]);
        $shipping->countryIds = array_map(fn ($countryState) => CountryId::fromString($countryState['country_id']), $childEntities[CountryId::class]);

        return $shipping;
    }
}
