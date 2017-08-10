<?php


namespace Thinktomorrow\Trader\Catalog\Products;


interface ProductRepository
{
    public function find($id): Product;

    public function add(Product $product, array $variants = []);

    public function findVariant($variantId): ProductVariant;
}