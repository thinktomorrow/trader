<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

final class InMemoryProductRepository implements ProductRepository, InMemoryRepository
{
    public static array $products = [];

    private string $nextReference = 'xxx-123';

    public static array $productTaxonLookup = [];

    public function save(Product $product): void
    {
        $this->extractVariantProperties($product);

        static::$products[$product->productId->get()] = $product;

        foreach ($product->getVariants() as $variant) {
            (new InMemoryVariantRepository())->save($variant);
        }

        static::$productTaxonLookup[$product->productId->get()] = array_map(fn (ProductTaxon $productTaxon) => $productTaxon->taxonId->get(), $product->getProductTaxa());
    }

    public static function getProductsFromLookup(string $taxonId): array
    {
        return array_keys(array_filter(static::$productTaxonLookup, fn ($taxonIds) => in_array($taxonId, $taxonIds)));
    }

    public static function getGridProductVariantPairsFromLookup(string $taxonId): array
    {
        $productIds = array_keys(array_filter(static::$productTaxonLookup, fn ($taxonIds) => in_array($taxonId, $taxonIds)));

        $pairs = [];

        // Get all variant ids for these products that are set to be shown in grid
        foreach ($productIds as $productId) {
            foreach (InMemoryVariantRepository::$variants as $variant) {
                if ($variant->productId->get() === $productId && $variant->showsInGrid()) {
                    $pairs[] = ['product_id' => $productId, 'variant_id' => $variant->variantId->get()];
                }
            }
        }

        return $pairs;
    }

    public function find(ProductId $productId): Product
    {
        if (! isset(static::$products[$productId->get()])) {
            throw new CouldNotFindProduct('No product found by id ' . $productId);
        }

        return static::$products[$productId->get()];
    }

    public function delete(ProductId $productId): void
    {
        if (! isset(static::$products[$productId->get()])) {
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

    public static function clear()
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
    public function getProductTaxonStatesByProduct(string $productId): array
    {
        if (! isset(static::$products[$productId])) {
            throw new CouldNotFindProduct('No product found by id ' . $productId);
        }

        return static::$products[$productId]->getChildEntities()[ProductTaxon::class] ?? [];
    }

    public function getProductTaxaByTaxonIds(string $productId, array $taxonIds): array
    {
        $taxa = InMemoryTaxonRepository::$taxons;

        $taxa = array_filter($taxa, fn ($taxon) => in_array($taxon->taxonId->get(), $taxonIds));

        return array_map(fn ($taxon) => ProductTaxon::fromMappedData(
            [
                'taxon_id' => $taxon->taxonId->get(),
                'data' => '{}',
            ],
            [
                'product_id' => $productId,
            ],
        ), $taxa);
    }

    private function extractVariantProperties(Product $product): void
    {
        $productTaxa = [];

        foreach ($product->getProductTaxa() as $i => $productTaxon) {
            if (isset(InMemoryTaxonRepository::$taxons[$productTaxon->taxonId->get()])) {
                $taxon = InMemoryTaxonRepository::$taxons[$productTaxon->taxonId->get()];
                $taxonomy = InMemoryTaxonomyRepository::$taxonomies[$taxon->taxonomyId->get()] ?? null;

                if ($taxonomy && $taxonomy->getType() === TaxonomyType::variant_property) {
                    $productTaxa[$i] = $productTaxon->toVariantProperty();

                    continue;
                }
            }

            $productTaxa[$i] = $productTaxon;
        }

        $product->updateProductTaxa($productTaxa);

        foreach ($product->getVariants() as $variant) {
            $this->extractVariantVariantProperties($variant);
        }
    }

    private function extractVariantVariantProperties(Variant $variant): void
    {
        $variantTaxa = [];

        foreach ($variant->getVariantTaxa() as $i => $variantTaxon) {
            if (isset(InMemoryTaxonRepository::$taxons[$variantTaxon->taxonId->get()])) {
                $taxon = InMemoryTaxonRepository::$taxons[$variantTaxon->taxonId->get()];
                $taxonomy = InMemoryTaxonomyRepository::$taxonomies[$taxon->taxonomyId->get()] ?? null;

                if ($taxonomy && $taxonomy->getType() === TaxonomyType::variant_property) {
                    $variantTaxa[$i] = $variantTaxon->toVariantProperty();

                    continue;
                }
            }

            $variantTaxa[$i] = $variantTaxon;
        }

        $variant->updateVariantTaxa($variantTaxa);
    }
}
