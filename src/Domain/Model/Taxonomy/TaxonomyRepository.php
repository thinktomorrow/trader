<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Taxonomy;

interface TaxonomyRepository
{
    public function save(Taxonomy $taxonomy): void;

    public function find(TaxonomyId $taxonomyId): Taxonomy;

    public function findManyByTaxa(array $taxonIds): array;

    public function delete(TaxonomyId $taxonomyId): void;

    public function nextReference(): TaxonomyId;
}
