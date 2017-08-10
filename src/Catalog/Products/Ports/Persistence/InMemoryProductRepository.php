<?php

namespace Thinktomorrow\Trader\Catalog\Products\Ports\Persistence;

use Assert\Assertion;
use Thinktomorrow\Trader\Catalog\Products\Product;
use Thinktomorrow\Trader\Catalog\Products\ProductRepository;
use Thinktomorrow\Trader\Catalog\Products\ProductVariant;

class InMemoryProductRepository implements ProductRepository
{
    private static $collection = [];
    private static $variantCollection = [];

    public function find($productId): Product
    {
        if(isset(self::$collection[$productId])) return self::$collection[$productId];

        throw new \RuntimeException('Product not found by id ['.$productId.']');
    }

    public function add(Product $product, array $variants = [])
    {
        Assertion::allIsInstanceOf($variants, ProductVariant::class);

        self::$collection[(string)$product->id()] = $product;

        // TODO: For now we don't set the connection of variants to product yet
        foreach($variants as $variant)
        {
            self::$variantCollection[$variant->id()] = $variant;
        }
    }

    public function findVariant($variantId): ProductVariant
    {
        if(isset(self::$variantCollection[$variantId])) return self::$variantCollection[$variantId];

        throw new \RuntimeException('Product variant not found by id ['.$variantId.']');
    }
}