<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;

class MysqlTaxonRepository implements TaxonRepository
{
    private static $taxonTable = 'trader_taxa';

    public function save(Taxon $taxon): void
    {
        $state = $taxon->getMappedData();

        if (!$this->exists($taxon->taxonId)) {
            DB::table(static::$taxonTable)->insert($state);
        } else {
            DB::table(static::$taxonTable)->where('taxon_id', $taxon->taxonId)->update($state);
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

        if (!$taxonState) {
            throw new CouldNotFindTaxon('No taxon found by id [' . $taxonId->get() . ']');
        }

        return Taxon::fromMappedData((array) $taxonState, []);
    }

    public function getByParentId(TaxonId $taxonId): array
    {
        $taxonStates = DB::table(static::$taxonTable)
            ->where(static::$taxonTable . '.parent_id', $taxonId->get())
            ->get();

        return $taxonStates
            ->map(fn($record) => Taxon::fromMappedData((array) $record, []))
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

    public function uniqueKeyReference(TaxonKey $taxonKey, TaxonId $allowedTaxonId): TaxonKey
    {
        $key = $taxonKey;

        while($this->existsByKey($key, $allowedTaxonId)) {
            $key = TaxonKey::fromString($taxonKey->get() . '_' . Str::random(3));
        }

        return $key;
    }

    private function existsByKey(TaxonKey $taxonKey, TaxonId $allowedTaxonId): bool
    {
        return DB::table(static::$taxonTable)
            ->where(static::$taxonTable . '.key', $taxonKey->get())
            ->where('taxon_id', '<>', $allowedTaxonId->get())
            ->exists();
    }
}
