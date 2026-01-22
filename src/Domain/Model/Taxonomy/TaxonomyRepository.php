<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxonomy;

use Thinktomorrow\Trader\Application\Taxonomy\TaxonomyItem;

interface TaxonomyRepository
{
    public function save(Taxonomy $taxonomy): void;

    public function find(TaxonomyId $taxonomyId): Taxonomy;

    public function findMany(array $taxonomyIds): array;

    public function findManyByTaxa(array $taxonIds): array;

    /**
     * Get all taxonomies that are set to be used
     * as grid filter in the front-end.
     *
     * @return TaxonomyItem[]
     */
    public function getForFilter(): array;

    /**
     * Find a taxonomy as grid filter in the front-end.
     */
    public function findForFilter(TaxonomyId $taxonomyId): TaxonomyItem;

    public function delete(TaxonomyId $taxonomyId): void;

    public function nextReference(): TaxonomyId;
}
