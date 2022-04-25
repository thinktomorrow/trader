<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;

final class InMemoryProductRepository implements ProductRepository
{
    public static array $products = [];

    private string $nextReference = 'xxx-123';

    public function save(Product $product): void
    {
        static::$products[$product->productId->get()] = $product;

        foreach($product->getVariants() as $variant) {
            InMemoryVariantRepository::$variants = array_merge(InMemoryVariantRepository::$variants, [
                $variant->variantId->get() => $variant,
            ]);
        }

    }

    public function find(ProductId $productId): Product
    {
        if(!isset(static::$products[$productId->get()])) {
            throw new CouldNotFindProduct('No product found by id ' . $productId);
        }

        return static::$products[$productId->get()];
    }

    public function delete(ProductId $productId): void
    {
        if(!isset(static::$products[$productId->get()])) {
            throw new CouldNotFindProduct('No product found by id ' . $productId);
        }

        unset(static::$products[$productId->get()]);
    }

    public function nextReference(): ProductId
    {
        return ProductId::fromString($this->nextReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function clear()
    {
        static::$products = [];
    }

//    public function findVariantForCart(VariantId $variantId): VariantForCart
//    {
//        foreach(static::$products as $product) {
//            foreach($product->getVariants() as $variant) {
//                if($variant->variantId->equals($variantId)) {
//                    return new VariantForCart(
//                        $variant->getSalePrice()
//                    );
//                }
//            }
//        }
//    }
}
