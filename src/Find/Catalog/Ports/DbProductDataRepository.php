<?php

namespace Thinktomorrow\Trader\Find\Catalog\Ports;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Find\Catalog\Domain\Product;
use Thinktomorrow\Trader\Find\Catalog\Reads\ProductFactory;
use Thinktomorrow\Trader\Find\Catalog\Domain\ProductRepository;

class DbProductRepository implements ProductRepository
{
    /** @var ProductFactory */
    private $productFactory;

    public function __construct(ProductFactory $productFactory)
    {
        $this->productFactory = $productFactory;
    }

    public function findById(int $id): Product
    {
        $record = DB::table('products')->where('id', $id)->first();

        return $this->productFactory->create($record);
    }
}
