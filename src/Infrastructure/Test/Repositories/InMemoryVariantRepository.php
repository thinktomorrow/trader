<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;
use Thinktomorrow\Trader\Domain\Model\Stock\Exceptions\CouldNotFindStockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\Exceptions\VariantRecordDoesNotExistWhenSavingStockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItem;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItemId;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItemRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultVariantForCart;

final class InMemoryVariantRepository implements InMemoryRepository, StockItemRepository, VariantForCartRepository, VariantRepository
{
    /** @var Variant[] */
    public static array $variants = [];

    public static array $stockItems = [];

    private static string $nextReference = 'xxx-123';

    public static array $variantTaxonLookup = [];

    public function save(Variant $variant): void
    {
        self::$variants[$variant->variantId->get()] = $variant;

        self::$variantTaxonLookup[$variant->variantId->get()] = array_map(fn ($taxon) => $taxon->taxonId->get(), $variant->getVariantTaxa());
    }

    public static function getGridProductVariantPairsFromLookup(string $taxonId): array
    {
        $variantIds = array_keys(array_filter(self::$variantTaxonLookup, fn ($taxonIds) => in_array($taxonId, $taxonIds)));

        $pairs = [];

        // Get all variant ids for these products that are set to be shown in grid
        foreach ($variantIds as $variantId) {
            foreach (InMemoryVariantRepository::$variants as $variant) {
                if ($variant->variantId->get() === $variantId && $variant->showsInGrid()) {
                    $pairs[] = ['product_id' => $variant->productId->get(), 'variant_id' => $variant->variantId->get()];
                }
            }
        }

        return $pairs;
    }

    /**
     * @return array with two entries: [0 => state data, 1 => child entities].
     *               Used internally by product repository to fetch all variants.
     */
    public function getStatesByProduct(ProductId $productId): array
    {
        $result = [];

        /** @var Variant $variant */
        foreach (self::$variants as $variant) {
            if ($variant->productId->equals($productId)) {

                $childEntities = $variant->getChildEntities();

                foreach ($childEntities as $type => $childEntitiesByType) {
                    if ($type == VariantTaxon::class) {
                        foreach ($childEntitiesByType as $index => $childEntity) {

                            // Find taxonomy type for this taxon id
                            $taxon = InMemoryTaxonRepository::$taxons[$childEntity['taxon_id']] ?? null;
                            $taxonomy = $taxon ? InMemoryTaxonomyRepository::$taxonomies[$taxon->taxonomyId->get()] : null;
                            $taxonomyType = $taxonomy ? $taxonomy->getType() : TaxonomyType::variant_property;

                            $childEntities[$type][$index]['taxonomy_type'] = $taxonomyType->value;
                        }
                    }
                }

                // Need to add taxonomy_type so the correct class is set
                $result[] = [$variant->getMappedData(), $childEntities];
            }
        }

        return $result;
    }

    public function delete(VariantId $variantId): void
    {
        if (! isset(self::$variants[$variantId->get()])) {
            throw new CouldNotFindVariant('No variant found by id '.$variantId);
        }

        unset(self::$variants[$variantId->get()]);
    }

    public function nextReference(): VariantId
    {
        return VariantId::fromString(self::$nextReference);
    }

    // For testing purposes only
    public static function setNextReference(string $nextReference): void
    {
        self::$nextReference = $nextReference;
    }

    public static function clear()
    {
        self::$variants = [];
    }

    public function findVariantForCart(VariantId $variantId): VariantForCart
    {
        foreach (self::$variants as $variant) {
            if ($variant->variantId->equals($variantId)) {
                return DefaultVariantForCart::fromMappedData(array_merge($variant->getMappedData(), ['product_data' => json_encode(InMemoryProductRepository::$products[$variant->productId->get()]->getData())]), $personalisations = $this->getPersonalisationsForVariant($variant));
            }
        }

        throw new CouldNotFindVariant('No variant found by id '.$variantId->get());
    }

    public function findAllVariantsForCart(array $variantIds): array
    {
        $result = [];

        foreach (self::$variants as $variant) {
            if (in_array($variant->variantId, $variantIds)) {
                $result[] = DefaultVariantForCart::fromMappedData(array_merge($variant->getMappedData(), ['product_data' => json_encode(InMemoryProductRepository::$products[$variant->productId->get()]->getData())]), $this->getPersonalisationsForVariant($variant));
            }
        }

        return $result;
    }

    /**
     * @return array|Personalisation[]
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

    public function findStockItem(StockItemId $stockItemId): StockItem
    {
        foreach (self::$stockItems as $stockItem) {
            if ($stockItem->stockItemId->equals($stockItemId)) {
                return $stockItem;
            }
        }

        throw new CouldNotFindStockItem('No stockitem found by id '.$stockItemId->get());
    }

    public function saveStockItem(StockItem $stockItem): void
    {
        // StockItem id must exist as variant!
        if (! isset(self::$variants[$stockItem->stockItemId->get()])) {
            throw new VariantRecordDoesNotExistWhenSavingStockItem;
        }

        self::$stockItems[] = $stockItem;
    }
}
