<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Test\Repositories;

use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Stock\StockItem;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultProductDetail;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultProductTaxonItem;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultVariantTaxonItem;

final class InMemoryProductDetailRepository implements ProductDetailRepository, InMemoryRepository
{
    public function findProductDetail(VariantId $variantId, bool $allowOffline = false): DefaultProductDetail
    {
        $variant = InMemoryVariantRepository::$variants[$variantId->get()];
        $product = InMemoryProductRepository::$products[$variant->productId->get()];
        $stock = InMemoryVariantRepository::$stockItems[$variantId->get()] ?? StockItem::fromMappedData([
            'stockitem_id' => $variantId->get(),
            'stock_level' => 5,
            'ignore_out_of_stock' => false,
            'stock_data' => json_encode([]),
        ]);

        if (! $allowOffline && ! in_array($product->getState(), ProductState::onlineStates())) {
            throw new CouldNotFindVariant('No online variant found by id [' . $variantId->get() . ']');
        }

        // Convert each taxon to the read model
        $taxa = [];

        $taxonomyRepo = new InMemoryTaxonomyRepository();
        $taxonRepo = new InMemoryTaxonRepository();

        foreach ($product->getProductTaxa() as $i => $productTaxon) {
            $taxon = $taxonRepo->find($productTaxon->taxonId);
            $taxonomy = $taxonomyRepo->find($taxon->taxonomyId);

            $keys = $taxon->getTaxonKeys();

            $taxa[] = new DefaultProductTaxonItem(
                $product->productId->get(),
                $productTaxon->taxonId->get(),
                $taxonomy->taxonomyId->get(),
                $taxonomy->getType(),
                $taxonomy->showsInGrid(),
                $taxon->getState(),
                $keys,
                [
                    'taxonomy_data' => $taxonomy->getData(),
                    'taxon_data' => $taxon->getData(),
                    ...$productTaxon->getData(),
                ]
            );
        }

        foreach ($product->getVariants() as $variant) {
            if ($variant->variantId->get() === $variantId->get()) {

                foreach ($variant->getVariantTaxa() as $variantTaxon) {
                    $taxon = $taxonRepo->find($productTaxon->taxonId);
                    $taxonomy = $taxonomyRepo->find($taxon->taxonomyId);

                    $keys = $taxon->getTaxonKeys();

                    $taxa[] = new DefaultVariantTaxonItem(
                        $variant->variantId->get(),
                        $product->productId->get(),
                        $variantTaxon->taxonId->get(),
                        $taxonomy->taxonomyId->get(),
                        $taxonomy->getType(),
                        $taxonomy->showsInGrid(),
                        $taxon->getState(),
                        $keys,
                        [
                            'taxonomy_data' => $taxonomy->getData(),
                            'taxon_data' => $taxon->getData(),
                            ...$variantTaxon->getData(),
                        ]
                    );
                }
            }
        }

        return DefaultProductDetail::fromMappedData(array_merge(($stock->getMappedData()), $variant->getMappedData(), [
            'product_data' => json_encode($product->getData()),
        ]), $taxa);
    }
}
