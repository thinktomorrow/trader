<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultVariantForCart;

final class InMemoryVariantRepository implements VariantRepository, VariantForCartRepository
{
    /** @var Variant[] */
    public static array $variants = [];
    private static string $nextReference = 'xxx-123';

    public function save(Variant $variant): void
    {
        static::$variants[$variant->variantId->get()] = $variant;
    }

    public function getStatesByProduct(ProductId $productId): array
    {
        $result = [];

        /** @var Variant $variant */
        foreach (static::$variants as $variant) {
            if ($variant->productId->equals($productId)) {
                $result[] = $variant->getMappedData();
            }
        }

        return $result;
    }

    public function delete(VariantId $variantId): void
    {
        if (! isset(static::$variants[$variantId->get()])) {
            throw new CouldNotFindVariant('No variant found by id ' . $variantId);
        }

        unset(static::$variants[$variantId->get()]);
    }

    public function nextReference(): VariantId
    {
        return VariantId::fromString(static::$nextReference);
    }

    // For testing purposes only
    public static function setNextReference(string $nextReference): void
    {
        static::$nextReference = $nextReference;
    }

    public static function clear()
    {
        static::$variants = [];
    }

    public function findVariantForCart(VariantId $variantId): VariantForCart
    {
        foreach (static::$variants as $variant) {
            if ($variant->variantId->equals($variantId)) {
                return DefaultVariantForCart::fromMappedData(array_merge($variant->getMappedData(), ['product_data' => json_encode(InMemoryProductRepository::$products[$variant->productId->get()]->getData())]), $personalisations = $this->getPersonalisationsForVariant($variant));
            }
        }

        throw new CouldNotFindVariant('No variant found by id ' . $variantId->get());
    }

    public function findAllVariantsForCart(array $variantIds): array
    {
        $result = [];

        foreach (static::$variants as $variant) {
            if (in_array($variant->variantId, $variantIds)) {
                $result[] = DefaultVariantForCart::fromMappedData(array_merge($variant->getMappedData(), ['product_data' => json_encode(InMemoryProductRepository::$products[$variant->productId->get()]->getData())]), $this->getPersonalisationsForVariant($variant));
            }
        }

        return $result;
    }

    /**
     * @param Variant $variant
     * @return array|\Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation[]
     */
    private function getPersonalisationsForVariant(Variant $variant): array
    {
        $personalisations = [];

        /** @var Product $product */
        foreach (InMemoryProductRepository::$products as $product) {
            if ($product->productId->equals($variant->productId)) {
                $personalisations = $product->getPersonalisations();
            }
        }

        return $personalisations;
    }
}
