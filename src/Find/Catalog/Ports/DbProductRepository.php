<?php

namespace Thinktomorrow\Trader\Find\Catalog\Ports;

use Assert\Assertion;
use Thinktomorrow\Trader\Find\Catalog\Reads\Product;
use Thinktomorrow\Trader\Find\Catalog\Reads\ProductRepository;

class DbProductRepository extends AbstractDbProductRepository implements ProductRepository
{
    public function findById($id): Product
    {
        $record = $this->initBuilder()->where($this->tableName().'.id', $id)->first();
        Assertion::notNull($record, "No product found by id [$id] in table [{$this->tableName()}]");

        $product = $this->createProductFromRecord($record);

        return $this->productFactory->create($product, $this->getAdjusterInstances());
    }
}
