<?php

declare(strict_types=1);

namespace Find\Catalog\Domain;

use Assert\Assertion;
use Common\Domain\Adjusters\AdjusterStrategy;

class ProductFactory
{
    use AdjusterStrategy;

    protected $adjusters = [

    ];

    public function create(Product $product, array $adjusterInstances): Product
    {
        $this->applyAdjusters($product, $adjusterInstances);

        return $product;
    }

    public function createMany(array $products, array $adjusterInstances): array
    {
        Assertion::allIsInstanceOf($products, Product::class);

        return array_map(function(Product $product) use($adjusterInstances){
            return $this->create($product, $adjusterInstances);
        }, $products);
    }
}
