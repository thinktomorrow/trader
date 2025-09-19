<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKeyId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;

final class InMemoryTaxonRepository implements TaxonRepository, InMemoryRepository
{
    /** @var Taxon[] */
    public static array $taxons = [];

    private string $nextReference = 'ccc-123';

    public function save(Taxon $taxon): void
    {
        static::$taxons[$taxon->taxonId->get()] = $taxon;
    }

    public function find(TaxonId $taxonId): Taxon
    {
        if (! isset(static::$taxons[$taxonId->get()])) {
            throw new CouldNotFindTaxon('No taxon found by id ' . $taxonId);
        }

        return static::$taxons[$taxonId->get()];
    }

    public function findMany(array $taxonIds): array
    {
        $output = [];

        foreach ($taxonIds as $taxonId) {
            if (isset(static::$taxons[$taxonId])) {
                $output[] = static::$taxons[$taxonId];
            }
        }

        return $output;
    }

    public function findByKey(TaxonKeyId $taxonKeyId): Taxon
    {
        foreach (static::$taxons as $taxon) {
            if ($taxon->hasTaxonKeyId($taxonKeyId)) {
                return $taxon;
            }
        }

        throw new CouldNotFindTaxon('No taxon found by key ' . $taxonKeyId->get());
    }

    public function getByParentId(TaxonId $taxonId): array
    {
        $output = [];

        foreach (static::$taxons as $taxon) {
            if ($taxon->getParentId()?->equals($taxonId)) {
                $output[] = $taxon;
            }
        }

        return $output;
    }

    public function delete(TaxonId $taxonId): void
    {
        if (! isset(static::$taxons[$taxonId->get()])) {
            throw new CouldNotFindTaxon('No taxon found by id ' . $taxonId);
        }

        unset(static::$taxons[$taxonId->get()]);
    }

    public function nextReference(): TaxonId
    {
        return TaxonId::fromString($this->nextReference);
    }

    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function uniqueKeyReference(TaxonKeyId $taxonKeyId, TaxonId $allowedTaxonId): TaxonKeyId
    {
        $key = $taxonKeyId;
        $i = 1;

        while ($this->existsByKey($key, $allowedTaxonId)) {
            $key = TaxonKeyId::fromString($taxonKeyId->get() . '_' . $i++);
        }

        return $key;
    }

    private function existsByKey(TaxonKeyId $taxonKeyId, TaxonId $allowedTaxonId): bool
    {
        foreach (static::$taxons as $taxon) {
            if (! $taxon->taxonId->equals($allowedTaxonId) && $taxon->hasTaxonKeyId($taxonKeyId)) {
                return true;
            }
        }

        return false;
    }

    public static function clear()
    {
        static::$taxons = [];
    }
}
