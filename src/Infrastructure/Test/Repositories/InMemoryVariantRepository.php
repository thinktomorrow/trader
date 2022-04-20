<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Application\Product\ProductOptions\VariantProductOptions;
use Thinktomorrow\Trader\Application\Product\ProductOptions\VariantProductOptionsRepository;
use Thinktomorrow\Trader\Application\Product\ProductOptions\VariantProductOptionsCollection;

final class InMemoryVariantRepository implements VariantRepository, VariantForCartRepository, VariantProductOptionsRepository
{
    private InMemoryProductRepository $productRepository;

    public static array $variants = [];
    private string $nextReference = 'xxx-123';

    public function __construct(InMemoryProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function save(Variant $variant): void
    {
        static::$variants[$variant->variantId->get()] = $variant;
    }

    public function getStatesByProduct(ProductId $productId): array
    {
        $result = [];

        /** @var Variant $variant */
        foreach(static::$variants as $variant) {
            if($variant->productId->equals($productId)) {
                $result[] = $variant->getMappedData();
            }
        }

        return $result;
    }

    public function delete(VariantId $variantId): void
    {
        if(!isset(static::$variants[$variantId->get()])) {
            throw new CouldNotFindVariant('No variant found by id ' . $variantId);
        }

        unset(static::$variants[$variantId->get()]);
    }

    public function nextReference(): VariantId
    {
        return VariantId::fromString($this->nextReference);
    }

    // For testing purposes only
    public function setNextReference(string $nextReference): void
    {
        $this->nextReference = $nextReference;
    }

    public function clear()
    {
        static::$variants = [];
    }

    public function findVariantForCart(VariantId $variantId): VariantForCart
    {
        foreach(static::$variants as $variant) {
            if($variant->variantId->equals($variantId)) {
                return new VariantForCart(
                    $variant->getSalePrice()
                );
            }
        }

        throw new CouldNotFindVariant('No variant found by id ' . $variantId->get());
    }

    public function getVariantProductOptions(ProductId $productId): VariantProductOptionsCollection
    {
        // TODO: get url... locale,

        $product = $this->productRepository->find($productId);
        $variants = $product->getVariants();
        $options = $product->getChildEntities()[Option::class];

        $variantForProductOptions = [];

        foreach($variants as $variant) {
            $variantOptionValueIds = $variant->getMappedData()['option_value_ids'];

            $variantOptionValues = [];
            foreach($options as $option) {
                foreach($option['values'] as $value) {
                    if(in_array($value['option_value_id'], $variantOptionValueIds)) {
                        $variantOptionValues[] = $value;
                    }
                }
            }

            $variantForProductOptions[] = VariantProductOptions::fromMappedData([
                'variant_id' => $variant->variantId->get(),
            ], $variantOptionValues);

        }

        return VariantProductOptionsCollection::fromType($variantForProductOptions);
    }
}
