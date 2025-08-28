<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Taxonomy\Exceptions\CouldNotFindTaxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyKeyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;

final class InMemoryTaxonomyRepository implements TaxonomyRepository
{
    /** @var Taxonomy[] */
    public static array $taxonomies = [];

    // Lookup of 'connected' product-taxonomy ids
    public static array $productIds = [];

    private string $nextReference = 'ccc-123';

    public function save(Taxonomy $taxonomy): void
    {
        self::$taxonomies[$taxonomy->taxonomyId->get()] = $taxonomy;
    }

    public function find(TaxonomyId $taxonomyId): Taxonomy
    {
        if (! isset(self::$taxonomies[$taxonomyId->get()])) {
            throw new CouldNotFindTaxonomy('No taxonomy found by id ' . $taxonomyId);
        }

        return self::$taxonomies[$taxonomyId->get()];
    }

    public function findManyByTaxa(array $taxonIds): array
    {
        $taxa = array_filter(InMemoryTaxonRepository::$taxons, fn ($taxon) => in_array($taxon->taxonId->get(), $taxonIds));
        $taxonomyIds = array_map(fn ($taxon) => $taxon->taxonomyId->get(), $taxa);

        return array_values(array_filter(self::$taxonomies, fn ($taxonomy) => in_array($taxonomy->taxonomyId->get(), $taxonomyIds)));
    }

    public function delete(TaxonomyId $taxonomyId): void
    {
        if (! isset(self::$taxonomies[$taxonomyId->get()])) {
            throw new CouldNotFindTaxonomy('No taxonomy found by id ' . $taxonomyId);
        }

        unset(self::$taxonomies[$taxonomyId->get()]);
    }

    public function nextReference(): TaxonomyId
    {
        return TaxonomyId::fromString($this->nextReference);
    }

    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function setProductIds(TaxonomyId $taxonomyId, array $productIds): void
    {
        static::$productIds[$taxonomyId->get()] = $productIds;
    }

    private function existsByKey(TaxonomyKeyId $taxonKeyId, TaxonomyId $allowedTaxonomyId): bool
    {
        foreach (self::$taxonomies as $taxonomy) {
            if (! $taxonomy->taxonomyId->equals($allowedTaxonomyId) && $taxonomy->hasTaxonomyKeyId($taxonKeyId)) {
                return true;
            }
        }

        return false;
    }

    public static function clear()
    {
        self::$taxonomies = [];
        static::$productIds = [];
    }
}
