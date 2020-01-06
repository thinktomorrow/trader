<?php

namespace Thinktomorrow\Trader\Find\Catalog\Ports\Laravel;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Find\Catalog\Domain\Product;
use Thinktomorrow\Trader\Find\Catalog\Domain\ProductId;
use Thinktomorrow\Trader\Find\Catalog\Reads\ProductFactory;
use Thinktomorrow\Trader\Find\Catalog\Domain\ProductRepository;

class DbProductDataRepository implements ProductRepository
{
    /** @var ProductFactory */
    private $productFactory;

    public function __construct(ProductFactory $productFactory)
    {
        $this->productFactory = $productFactory;
    }

    public function findById(ProductId $productId): Product
    {
        $record = DB::table('products')->where('id', $productId->get())->first();

        return $this->productFactory->create($record);
    }
}
