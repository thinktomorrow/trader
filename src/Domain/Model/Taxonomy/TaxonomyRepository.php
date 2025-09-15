<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxonomy;

interface TaxonomyRepository
{
    public function save(Taxonomy $taxonomy): void;

    public function find(TaxonomyId $taxonomyId): Taxonomy;

    public function findMany(array $taxonomyIds): array;

    public function findManyByTaxa(array $taxonIds): array;

    /**
     * Get all taxonomies that are set to be used
     * as grid filter in the front-end.
     */
    public function getForFilter(): array;

    public function delete(TaxonomyId $taxonomyId): void;

    public function nextReference(): TaxonomyId;
}
