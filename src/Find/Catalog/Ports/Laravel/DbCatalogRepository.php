<?php

namespace Thinktomorrow\Trader\Find\Catalog\Ports\Laravel;

use Thinktomorrow\Trader\Find\Catalog\Reads\CatalogRepository;

class DbCatalogRepository extends AbstractDbProductRepository implements CatalogRepository
{
    public function getAll(): array
    {
        $records = $this->initBuilder()->get();

        $adjusterInstances = $this->getAdjusterInstances();

        return $records->map(function($record) use($adjusterInstances){
            return $this->productFactory->create(
                $this->createProductFromRecord($record),
                $adjusterInstances
            );
        })->toArray();
    }
}
