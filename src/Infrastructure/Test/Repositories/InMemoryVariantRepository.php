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
use Thinktomorrow\Trader\Application\Product\ProductOptions\VariantForProductOption;
use Thinktomorrow\Trader\Application\Product\ProductOptions\VariantForProductOptionRepository;
use Thinktomorrow\Trader\Application\Product\ProductOptions\VariantForProductOptionCollection;

final class InMemoryVariantRepository implements VariantRepository, VariantForCartRepository, VariantForProductOptionRepository
{
    private InMemoryProductRepository $productRepository;

    private static array $variants = [];
    private string $nextReference = 'xxx-123';

    public function __construct(InMemoryProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function save(Variant $variant): void
    {
        static::$variants[$variant->variantId->get()] = $variant;
    }

    public function find(VariantId $variantId): Variant
    {
        if(!isset(static::$variants[$variantId->get()])) {
            throw new CouldNotFindVariant('No variant found by id ' . $variantId);
        }

        return static::$variants[$variantId->get()];
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

    public function getVariantsForProductOption(ProductId $productId): VariantForProductOptionCollection
    {
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

            $variantForProductOptions[] = VariantForProductOption::fromMappedData([
                'variant_id' => $variant->variantId->get(),
            ], $variantOptionValues);

        }

        return VariantForProductOptionCollection::fromType($variantForProductOptions);
    }
}
