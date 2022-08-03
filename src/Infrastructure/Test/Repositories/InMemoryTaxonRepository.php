<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonKey;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;

final class InMemoryTaxonRepository implements TaxonRepository
{
    /** @var Taxon[] */
    public static array $taxons = [];

    // Lookup of 'connected' product-taxon ids
    public static array $productIds = [];

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

    public function findByKey(TaxonKey $taxonKey): Taxon
    {
        foreach (static::$taxons as $taxon) {
            if ($taxon->taxonKey->equals($taxonKey)) {
                return $taxon;
            }
        }

        throw new CouldNotFindTaxon('No taxon found by key ' . $taxonKey->get());
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

    public function setProductIds(TaxonId $taxonId, array $productIds): void
    {
        static::$productIds[$taxonId->get()] = $productIds;
    }

    public function uniqueKeyReference(TaxonKey $taxonKey, TaxonId $allowedTaxonId): TaxonKey
    {
        $key = $taxonKey;
        $i = 1;

        while ($this->existsByKey($key, $allowedTaxonId)) {
            $key = TaxonKey::fromString($taxonKey->get() . '_' . $i++);
        }

        return $key;
    }

    private function existsByKey(TaxonKey $taxonKey, TaxonId $allowedTaxonId): bool
    {
        foreach (static::$taxons as $taxon) {
            if (! $taxon->taxonId->equals($allowedTaxonId) && $taxon->taxonKey->equals($taxonKey)) {
                return true;
            }
        }

        return false;
    }

    public static function clear()
    {
        static::$taxons = [];
        static::$productIds = [];
    }
}
