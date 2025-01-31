<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\TaxRateProfile;

use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\Country\HasCountryIds;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\Events\TaxRateDoubleDeleted;

final class TaxRateProfile implements Aggregate
{
    use RecordsEvents;
    use HasCountryIds;
    use HasData;

    public readonly TaxRateProfileId $taxRateProfileId;
    private TaxRateProfileState $state;

    /** @var TaxRateDouble[] */
    private array $taxRateDoubles = [];

    public static function create(TaxRateProfileId $taxRateProfileId): static
    {
        $shippingProfile = new static();
        $shippingProfile->taxRateProfileId = $taxRateProfileId;
        $shippingProfile->state = TaxRateProfileState::online;

        return $shippingProfile;
    }

    public function updateState(TaxRateProfileState $state): void
    {
        $this->state = $state;
    }

    public function getState(): TaxRateProfileState
    {
        return $this->state;
    }

    public function getTaxRateDoubles(): array
    {
        return $this->taxRateDoubles;
    }

    public function findTaxRateDoubleByOriginal(TaxRate $taxRate): ?TaxRateDouble
    {
        foreach ($this->taxRateDoubles as $double) {
            if ($double->hasOriginalRate($taxRate)) {
                return $double;
            }
        }

        return null;
    }

    public function findTaxRateDouble(TaxRateDoubleId $taxRateDoubleId): TaxRateDouble
    {
        foreach ($this->taxRateDoubles as $double) {
            if ($double->taxRateDoubleId->equals($taxRateDoubleId)) {
                return $double;
            }
        }

        throw new \InvalidArgumentException('No TaxRateDouble found by id ' . $taxRateDoubleId->get());
    }

    public function addTaxRateDouble(TaxRateDouble $taxRateDouble): void
    {
        $this->taxRateDoubles[] = $taxRateDouble;
    }

    public function deleteTaxRateDouble(TaxRateDoubleId $taxRateDoubleId): void
    {
        foreach ($this->taxRateDoubles as $i => $double) {
            if ($double->taxRateDoubleId->equals($taxRateDoubleId)) {
                unset($this->taxRateDoubles[$i]);
                $this->recordEvent(new TaxRateDoubleDeleted($this->taxRateProfileId, $taxRateDoubleId));
            }
        }
    }

    public function getMappedData(): array
    {
        return [
            'taxrate_profile_id' => $this->taxRateProfileId->get(),
            'state' => $this->state->value,
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            TaxRateDouble::class => array_map(fn (TaxRateDouble $taxRateDouble) => $taxRateDouble->getMappedData(), $this->taxRateDoubles),
            CountryId::class => array_map(fn (CountryId $countryId) => $countryId->get(), $this->countryIds),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $object = new static();
        $object->taxRateProfileId = TaxRateProfileId::fromString($state['taxrate_profile_id']);
        $object->state = TaxRateProfileState::from($state['state']);
        $object->data = json_decode($state['data'], true);

        $object->taxRateDoubles = array_map(fn ($taxRateDoubleState) => TaxRateDouble::fromMappedData($taxRateDoubleState, $state), $childEntities[TaxRateDouble::class]);
        $object->countryIds = array_map(fn ($countryState) => CountryId::fromString($countryState['country_id']), $childEntities[CountryId::class]);

        return $object;
    }
}
