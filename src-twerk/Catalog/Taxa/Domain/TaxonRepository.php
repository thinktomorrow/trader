<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Taxa\Domain;

use Illuminate\Support\Collection;
use Thinktomorrow\Vine\NodeCollection;

interface TaxonRepository
{
//    public function getChildren(string $parentId): Collection;

//    public function getTopChildren(string $parentKey): Collection;

//    public function getGrandChildren(string $parentKey): Collection;

    public function findById(string $id): ?Taxon;

    public function findByKey(string $key): ?Taxon;

    public function findManyByKeys(array $keys): NodeCollection;

    public function create(array $values, ?Taxon $parent): Taxon;

    public function save(Taxon $taxon): void;

    public function delete(Taxon $taxon): void;

    public function getRootNodes(): NodeCollection;
}
