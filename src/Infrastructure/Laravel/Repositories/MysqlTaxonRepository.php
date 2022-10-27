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
        $taxonState = DB::table(static::$taxonTable)
            ->where(static::$taxonTable . '.taxon_id', $taxonId->get())
            ->first();

        $taxonKeyStates = DB::table(static::$taxonKeysTable)
            ->where(static::$taxonKeysTable . '.taxon_id', $taxonId->get())
            ->get()
            ->map(fn ($item) => (array) $item)
            ->toArray();

        if (! $taxonState) {
            throw new CouldNotFindTaxon('No taxon found by id [' . $taxonId->get() . ']');
        }

        return Taxon::fromMappedData((array) $taxonState, [TaxonKey::class => $taxonKeyStates]);
    }

    public function findByKey(TaxonKeyId $taxonKeyId): Taxon
    {
        $taxonState = DB::table(static::$taxonTable)
            ->join(static::$taxonKeysTable, static::$taxonTable.'.taxon_id', '=', static::$taxonKeysTable.'.taxon_id')
            ->where(static::$taxonKeysTable . '.key', $taxonKeyId->get())
            ->select(static::$taxonTable . '.*')
            ->first();

        if (! $taxonState) {
            throw new CouldNotFindTaxon('No taxon found by key [' . $taxonKeyId->get() . ']');
        }

        $taxonKeyStates = DB::table(static::$taxonKeysTable)
            ->where(static::$taxonKeysTable . '.taxon_id', $taxonState->taxon_id)
            ->get()
            ->map(fn ($item) => (array) $item)
            ->toArray();

        return Taxon::fromMappedData((array) $taxonState, [TaxonKey::class => $taxonKeyStates]);
    }

    public function getByParentId(TaxonId $taxonId): array
    {
        $taxonStates = DB::table(static::$taxonTable)
            ->where(static::$taxonTable . '.parent_id', $taxonId->get())
            ->get();

        return $taxonStates
            ->map(fn ($record) => Taxon::fromMappedData((array) $record, []))
            ->toArray();
    }

    public function delete(TaxonId $taxonId): void
    {
        DB::table(static::$taxonTable)->where('taxon_id', $taxonId->get())->delete();
    }

    public function nextReference(): TaxonId
    {
        return TaxonId::fromString((string) Uuid::uuid4());
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
