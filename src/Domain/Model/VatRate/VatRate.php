<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\VatRate;

use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Common\Taxes\TaxRate;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\Events\BaseVatRateDeleted;

final class VatRate implements Aggregate
{
    use RecordsEvents;
    use HasData;

    public readonly VatRateId $vatRateId;
    public readonly CountryId $countryId;

    private TaxRate $rate;

    /**
     * Is this rate the country's standard (primary) vat rate?
     */
    private bool $isStandard = false;

    private VatRateState $state;

    /**
     * Mapping of any the primary country rates to this rate
     * @var VatRate[]
     */
    private array $baseRates = [];

    public static function create(VatRateId $vatRateId, CountryId $countryId, TaxRate $rate, bool $isStandard): static
    {
        $object = new static();
        $object->vatRateId = $vatRateId;
        $object->countryId = $countryId;
        $object->rate = $rate;
        $object->isStandard = $isStandard;
        $object->state = VatRateState::online;

        return $object;
    }

    public function updateState(VatRateState $state): void
    {
        $this->state = $state;
    }

    public function getState(): VatRateState
    {
        return $this->state;
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

    public function hasBaseRate(TaxRate $taxRate): ?VatRate
    {
        foreach ($this->baseRates as $baseRates) {
            if ($baseRates->rate->equals($taxRate)) {
                return $baseRates;
            }
        }

        return null;
    }

    public function findBaseRate(VatRateId $vatRateId): VatRate
    {
        foreach ($this->baseRates as $baseRate) {
            if ($baseRate->vatRateId->equals($vatRateId)) {
                return $baseRate;
            }
        }

        throw new \InvalidArgumentException('No baseRate found by id ' . $vatRateId->get());
    }

    public function addBaseRate(VatRate $vatRate): void
    {
        $this->baseRates[] = $vatRate;
    }

    public function deleteBaseRate(VatRateId $vatRateId): void
    {
        foreach ($this->baseRates as $i => $baseRate) {
            if ($baseRate->vatRateId->equals($vatRateId)) {
                unset($this->baseRates[$i]);
                $this->recordEvent(new BaseVatRateDeleted($this->vatRateId, $vatRateId));
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
            VatRate::class => array_map(fn (VatRate $vatRate) => $vatRate->getMappedData(), $this->baseRates),
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $object = new static();
        $object->vatRateId = VatRateId::fromString($state['vat_rate_id']);
        $object->countryId = CountryId::fromString($state['country_id']);
        $object->rate = TaxRate::fromString($state['rate']);
        $object->isStandard = $state['is_standard'];
        $object->state = VatRateState::from($state['state']);
        $object->data = json_decode($state['data'], true);
        $object->baseRates = array_map(fn ($vatRateState) => VatRate::fromMappedData($vatRateState, $state), $childEntities[VatRate::class]);

        return $object;
    }
}
