<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\BaseRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\Exceptions\CouldNotFindVatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateId;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateRepository;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateState;
use Thinktomorrow\Trader\Infrastructure\Laravel\config\TraderConfig;

class MysqlVatRateRepository implements VatRateRepository
{
    private static $vatRateTable = 'trader_vat_rates';
    private static $baseRateTable = 'trader_vat_base_rates';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function save(VatRate $vatRate): void
    {
        $state = $vatRate->getMappedData();

        if (! $this->exists($vatRate->vatRateId)) {
            DB::table(static::$vatRateTable)->insert($state);
        } else {
            DB::table(static::$vatRateTable)->where('vat_rate_id', $vatRate->vatRateId->get())->update($state);
        }

        $this->upsertBaseRates($vatRate);
    }

    private function upsertBaseRates(VatRate $vatRate): void
    {
        DB::table(static::$baseRateTable)
            ->where('target_vat_rate_id', $vatRate->vatRateId->get())
            ->delete();

        DB::table(static::$baseRateTable)
            ->insert($vatRate->getChildEntities()[BaseRate::class]);
    }

    private function exists(VatRateId $vatRateId): bool
    {
        return DB::table(static::$vatRateTable)->where('vat_rate_id', $vatRateId->get())->exists();
    }

    public function find(VatRateId $vatRateId): VatRate
    {
        $vatRateState = DB::table(static::$vatRateTable)
            ->where(static::$vatRateTable . '.vat_rate_id', $vatRateId->get())
            ->first();

        if (! $vatRateState) {
            throw new CouldNotFindVatRate('No vatRate found by id [' . $vatRateId->get() . ']');
        }

        return $this->makeWithChildEntities($vatRateId->get(), $vatRateState);
    }

    public function delete(VatRateId $vatRateId): void
    {
        DB::table(static::$vatRateTable)->where('vat_rate_id', $vatRateId->get())->delete();
    }

    public function nextReference(): VatRateId
    {
        return VatRateId::fromString((string)Uuid::uuid4());
    }

    public function nextBaseRateReference(): BaseRateId
    {
        return BaseRateId::fromString((string)Uuid::uuid4());
    }

    private function makeWithChildEntities(string $vatRateId, $vatRateState): VatRate
    {
        $vatRateState = (array)$vatRateState;
        $vatRateState['is_standard'] = (bool)$vatRateState['is_standard'];

        $baseRateStates = DB::table(static::$baseRateTable)
            ->join(static::$vatRateTable, static::$baseRateTable . '.origin_vat_rate_id', '=', static::$vatRateTable . '.vat_rate_id')
            ->where(static::$baseRateTable . '.target_vat_rate_id', $vatRateId)
            ->select([static::$baseRateTable . '.*', static::$vatRateTable . '.rate'])
            ->get()
            ->map(fn ($item) => (array)$item)
            ->toArray();

        return VatRate::fromMappedData((array)$vatRateState, [
            BaseRate::class => $baseRateStates,
        ]);
    }

    public function getVatRatesForCountry(CountryId $countryId): array
    {
        $vatRateStates = DB::table(static::$vatRateTable)
            ->where('country_id', $countryId->get())
            ->whereIn('state', VatRateState::onlineStates())
            ->orderBy('order_column', 'ASC')
            ->get();

        return collect($vatRateStates)->map(function ($vatRateState) {
            return $this->makeWithChildEntities($vatRateState->vat_rate_id, $vatRateState);
        })->all();
    }

    public function findStandardVatRateForCountry(CountryId $countryId): ?VatRate
    {
        $vatRateState = DB::table(static::$vatRateTable)
            ->where('country_id', $countryId->get())
            ->where('is_standard', true)
            ->whereIn('state', VatRateState::onlineStates())
            ->orderBy('order_column', 'ASC')
            ->first();

        if (! $vatRateState) {
            return null;
        }

        return $this->makeWithChildEntities($vatRateState->vat_rate_id, $vatRateState);
    }

    public function getPrimaryVatRates(): array
    {
        $primaryCountryId = CountryId::fromString(app(TraderConfig::class)->getPrimaryVatCountry());

        return $this->getVatRatesForCountry($primaryCountryId);
    }

    public function getStandardPrimaryVatRate(): VatPercentage
    {
        $primaryCountryId = CountryId::fromString(app(TraderConfig::class)->getPrimaryVatCountry());

        $standardVatRate = $this->findStandardVatRateForCountry($primaryCountryId);

        if (! $standardVatRate) {
            return VatPercentage::fromString(app(TraderConfig::class)->getFallBackStandardVatRate());
        }

        return $standardVatRate->getRate();
    }
}
