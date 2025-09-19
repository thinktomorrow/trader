<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\Exceptions\CouldNotFindVatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateRepository;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateState;

final class InMemoryVatRateRepository implements VatRateRepository, InMemoryRepository
{
    /** @var VatRate[] */
    private static array $vatRates = [];

    private ?string $nextReference = null;
    private ?string $nextBaseRateReference = null;

    private \Thinktomorrow\Trader\TraderConfig $traderConfig;

    public function __construct(\Thinktomorrow\Trader\TraderConfig $traderConfig)
    {
        $this->traderConfig = $traderConfig;
    }

    public function save(VatRate $vatRate): void
    {
        static::$vatRates[$vatRate->vatRateId->get()] = $vatRate;
    }

    public function find(VatRateId $vatRateId): VatRate
    {
        if (! isset(static::$vatRates[$vatRateId->get()])) {
            throw new CouldNotFindVatRate('No vatRate found by id ' . $vatRateId);
        }

        return static::$vatRates[$vatRateId->get()];
    }

    public function delete(VatRateId $vatRateId): void
    {
        if (! isset(static::$vatRates[$vatRateId->get()])) {
            throw new CouldNotFindVatRate('No available vatRate found by id ' . $vatRateId);
        }

        unset(static::$vatRates[$vatRateId->get()]);
    }

    public function nextReference(): VatRateId
    {
        if (! $this->nextReference) {
            return VatRateId::fromString('vatRate-' . Uuid::uuid4());
        }

        return VatRateId::fromString($this->nextReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public static function clear()
    {
        static::$vatRates = [];
    }

    public function nextBaseRateReference(): BaseRateId
    {
        if (! $this->nextBaseRateReference) {
            return BaseRateId::fromString('baseRate-' . Uuid::uuid4());
        }

        return BaseRateId::fromString($this->nextBaseRateReference);
    }

    public function getVatRatesForCountry(CountryId $countryId): array
    {
        $rates = [];

        foreach (static::$vatRates as $vatRate) {
            if ($vatRate->getState() == VatRateState::online && $vatRate->countryId->equals($countryId)) {
                $rates[] = $vatRate;
            }
        }

        return $rates;
    }

    public function findStandardVatRateForCountry(CountryId $countryId): ?VatRate
    {
        foreach (static::$vatRates as $vatRate) {
            if ($vatRate->getState() == VatRateState::online && $vatRate->isStandard() && $vatRate->countryId->equals($countryId)) {
                return $vatRate;
            }
        }

        return null;
    }


    public function getPrimaryVatRates(): array
    {
        $primaryCountryId = CountryId::fromString($this->traderConfig->getPrimaryVatCountry());

        return $this->getVatRatesForCountry($primaryCountryId);
    }

    public function getStandardPrimaryVatRate(): VatPercentage
    {
        $primaryCountryId = CountryId::fromString($this->traderConfig->getPrimaryVatCountry());

        $standardVatRate = $this->findStandardVatRateForCountry($primaryCountryId);

        if (! $standardVatRate) {
            return VatPercentage::fromString($this->traderConfig->getFallBackStandardVatRate());
        }

        return $this->findStandardVatRateForCountry($primaryCountryId)->getRate();
    }
}
