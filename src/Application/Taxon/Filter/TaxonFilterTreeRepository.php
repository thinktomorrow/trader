<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Filter;

interface TaxonFilterTreeRepository
{
    public function findByKey(string $key): ?TaxonFilter;

    public function findManyByKeys(array $keys): TaxonFilterTree;

    public function getTree(): TaxonFilterTree;
}
