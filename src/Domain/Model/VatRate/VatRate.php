<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\VatRate;

use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\Events\BaseRateDeleted;

final class VatRate implements Aggregate
{
    use RecordsEvents;
    use HasData;

    public readonly VatRateId $vatRateId;
    public readonly CountryId $countryId;

    private VatPercentage $rate;

    /**
     * Is this rate the country's standard (primary) vat rate?
     */
    private bool $isStandard = false;

    private VatRateState $state;

    /**
     * Mapping of any the primary country rates to this rate
     * @var BaseRate[]
     */
    private array $baseRates = [];

    public static function create(VatRateId $vatRateId, CountryId $countryId, VatPercentage $rate, bool $isStandard): static
    {
        $object = new static();
        $object->vatRateId = $vatRateId;
        $object->countryId = $countryId;
        $object->rate = $rate;
        $object->isStandard = $isStandard;
        $object->state = VatRateState::online;

        return $object;
    }

    public function getRate(): VatPercentage
    {
        return $this->rate;
    }

    public function updateRate(VatPercentage $rate): void
    {
        $this->rate = $rate;
    }

    public function getState(): VatRateState
    {
        return $this->state;
    }

    public function updateState(VatRateState $state): void
    {
        $this->state = $state;
    }

    public function isStandard(): bool
    {
        return $this->isStandard;
    }

    public function setAsStandard(): void
    {
        $this->isStandard = true;
    }

    public function unsetAsStandard(): void
    {
        $this->isStandard = false;
    }

    public function getBaseRates(): array
    {
        return $this->baseRates;
    }

    public function hasBaseRateOf(VatPercentage $vatRateValue): bool
    {
        foreach ($this->baseRates as $baseRate) {
            if ($baseRate->rate->equals($vatRateValue)) {
                return true;
            }
        }

        return false;
    }

    public function findBaseRate(BaseRateId $baseRateId): BaseRate
    {
        foreach ($this->baseRates as $baseRates) {
            if ($baseRates->baseRateId->equals($baseRateId)) {
                return $baseRates;
            }
        }

        throw new \InvalidArgumentException('No base rate found by id ' . $baseRateId->get());
    }

    public function addBaseRate(BaseRate $baseRate): void
    {
        $this->baseRates[] = $baseRate;
    }

    public function deleteBaseRate(BaseRateId $baseRateId): void
    {
        foreach ($this->baseRates as $i => $baseRates) {
            if ($baseRates->baseRateId->equals($baseRateId)) {
                unset($this->baseRates[$i]);
                $this->recordEvent(new BaseRateDeleted($baseRateId, $this->vatRateId));
            }
        }
    }

    public function getMappedData(): array
    {
        return [
            'vat_rate_id' => $this->vatRateId->get(),
            'country_id' => $this->countryId->get(),
            'rate' => $this->rate->get(),
            'is_standard' => $this->isStandard,
            'state' => $this->state->value,
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            BaseRate::class => array_map(fn (BaseRate $baseRate) => $baseRate->getMappedData(), $this->baseRates),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $object = new static();
        $object->vatRateId = VatRateId::fromString($state['vat_rate_id']);
        $object->countryId = CountryId::fromString($state['country_id']);
        $object->rate = VatPercentage::fromString($state['rate']);
        $object->isStandard = $state['is_standard'];
        $object->state = VatRateState::from($state['state']);
        $object->data = json_decode($state['data'], true);
        $object->baseRates = array_map(fn ($baseRateState) => BaseRate::fromMappedData($baseRateState, $state), $childEntities[BaseRate::class]);

        return $object;
    }
}
