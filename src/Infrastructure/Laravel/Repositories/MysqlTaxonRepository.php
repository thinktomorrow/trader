<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKeyId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;

class MysqlTaxonRepository implements TaxonRepository
{
    use WithTaxonKeysSelection;

    private static $taxonTable = 'trader_taxa';
    private static $taxonKeysTable = 'trader_taxa_keys';

    public function save(Taxon $taxon): void
    {
        $state = $taxon->getMappedData();

        if (! $this->exists($taxon->taxonId)) {
            DB::table(static::$taxonTable)->insert($state);
        } else {
            DB::table(static::$taxonTable)->where('taxon_id', $taxon->taxonId->get())->update($state);
        }

        $this->upsertTaxonKeys($taxon);
    }

    private function upsertTaxonKeys(Taxon $taxon): void
    {
        $taxonKeyIds = array_map(fn (TaxonKey $taxonKey) => $taxonKey->taxonKeyId->get(), $taxon->getTaxonKeys());

        DB::table(static::$taxonKeysTable)
            ->where('taxon_id', $taxon->taxonId->get())
            ->whereNotIn('key', $taxonKeyIds)
            ->delete();

        foreach ($taxon->getTaxonKeys() as $taxonKey) {
            DB::table(static::$taxonKeysTable)
                ->updateOrInsert([
                    'taxon_id' => $taxonKey->taxonId->get(),
                    'key' => $taxonKey->taxonKeyId->get(),
                ], $taxonKey->getMappedData());
        }
    }

    private function exists(TaxonId $taxonId): bool
    {
        return DB::table(static::$taxonTable)->where('taxon_id', $taxonId->get())->exists();
    }

    public function find(TaxonId $taxonId): Taxon
    {
        $taxonKeysSelect = $this->composeTaxonKeysSelect();

        $taxonState = DB::table(static::$taxonTable)
            ->leftJoin(static::$taxonKeysTable, static::$taxonTable . '.taxon_id', '=', static::$taxonKeysTable . '.taxon_id')
            ->select([
                static::$taxonTable . '.*',
                DB::raw("GROUP_CONCAT(DISTINCT $taxonKeysSelect) AS taxon_keys"),
            ])
            ->where(static::$taxonTable . '.taxon_id', $taxonId->get())
            ->groupBy(static::$taxonTable . '.taxon_id')
            ->first();

        $taxonKeyStates = $this->extractTaxonKeys((array)$taxonState);

        if (! $taxonState) {
            throw new CouldNotFindTaxon('No taxon found by id [' . $taxonId->get() . ']');
        }

        return Taxon::fromMappedData((array)$taxonState, [TaxonKey::class => $taxonKeyStates]);
    }

    public function findMany(array $taxonIds): array
    {
        $taxonKeysSelect = $this->composeTaxonKeysSelect();

        $taxonStates = DB::table(static::$taxonTable)
            ->leftJoin(static::$taxonKeysTable, static::$taxonTable . '.taxon_id', '=', static::$taxonKeysTable . '.taxon_id')
            ->select([
                static::$taxonTable . '.*',
                DB::raw("GROUP_CONCAT(DISTINCT $taxonKeysSelect) AS taxon_keys"),
            ])
            ->whereIn(static::$taxonTable . '.taxon_id', $taxonIds)
            ->groupBy(static::$taxonTable . '.taxon_id')
            ->get();

        return $taxonStates
            ->map(fn ($record) => Taxon::fromMappedData((array)$record, [TaxonKey::class => $this->extractTaxonKeys((array)$record)]))
            ->toArray();
    }

    public function findByKey(TaxonKeyId $taxonKeyId): Taxon
    {
        $taxonKeysSelect = $this->composeTaxonKeysSelect();

        $taxonState = DB::table(static::$taxonTable)
            ->join(static::$taxonKeysTable, static::$taxonTable . '.taxon_id', '=', static::$taxonKeysTable . '.taxon_id')
            ->select([
                static::$taxonTable . '.*',
                DB::raw("GROUP_CONCAT(DISTINCT $taxonKeysSelect) AS taxon_keys"),
            ])
            ->where(static::$taxonTable . '.taxon_id', function ($query) use ($taxonKeyId) {
                $query->select(static::$taxonKeysTable . '.taxon_id')
                    ->from(static::$taxonKeysTable)
                    ->where(static::$taxonKeysTable . '.key', $taxonKeyId->get())
                    ->limit(1);
            })
            ->groupBy(static::$taxonTable . '.taxon_id')
            ->first();

        if (! $taxonState) {
            throw new CouldNotFindTaxon('No taxon found by key [' . $taxonKeyId->get() . ']');
        }

        $taxonKeyStates = $this->extractTaxonKeys((array)$taxonState);

        return Taxon::fromMappedData((array)$taxonState, [TaxonKey::class => $taxonKeyStates]);
    }

    public function getByParentId(TaxonId $taxonId): array
    {
        $taxonIds = DB::table(static::$taxonTable)
            ->where(static::$taxonTable . '.parent_id', $taxonId->get())
            ->select(static::$taxonTable . '.taxon_id')
            ->pluck('taxon_id')
            ->all();

        return $this->findMany($taxonIds);
    }

    public function delete(TaxonId $taxonId): void
    {
        DB::table(static::$taxonTable)->where('taxon_id', $taxonId->get())->delete();
    }

    public function nextReference(): TaxonId
    {
        return TaxonId::fromString((string)Uuid::uuid4());
    }

    public function uniqueKeyReference(TaxonKeyId $taxonKeyId, TaxonId $allowedTaxonId): TaxonKeyId
    {
        $key = $taxonKeyId;

        while ($this->existsByKey($key, $allowedTaxonId)) {
            $key = TaxonKeyId::fromString($taxonKeyId->get() . '_' . Str::random(3));
        }

        return $key;
    }

    private function existsByKey(TaxonKeyId $taxonKeyId, TaxonId $allowedTaxonId): bool
    {
        return DB::table(static::$taxonKeysTable)
            ->where(static::$taxonKeysTable . '.key', $taxonKeyId->get())
            ->where('taxon_id', '<>', $allowedTaxonId->get())
            ->exists();
    }
}
