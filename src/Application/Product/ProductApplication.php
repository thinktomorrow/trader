<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product;

use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductData;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductPersonalisations;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductTaxa;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateVariantTaxa;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\Personalisation;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\TraderConfig;

class ProductApplication
{
    private TraderConfig $traderConfig;
    private EventDispatcher $eventDispatcher;
    private ProductRepository $productRepository;
    private VariantRepository $variantRepository;
    private TaxonTreeRepository $taxonTreeRepository;
    private TaxonomyRepository $taxonomyRepository;

    public function __construct(
        TraderConfig        $traderConfig,
        EventDispatcher     $eventDispatcher,
        ProductRepository   $productRepository,
        VariantRepository   $variantRepository,
        TaxonTreeRepository $taxonTreeRepository,
        TaxonomyRepository  $taxonomyRepository,
    ) {
        $this->traderConfig = $traderConfig;
        $this->eventDispatcher = $eventDispatcher;
        $this->productRepository = $productRepository;
        $this->variantRepository = $variantRepository;
        $this->taxonTreeRepository = $taxonTreeRepository;
        $this->taxonomyRepository = $taxonomyRepository;
    }

    public function createProduct(CreateProduct $createProduct): ProductId
    {
        $productId = $this->productRepository->nextReference();

        $product = Product::create($productId);

        // Create ProductTaxon objects per taxonId and add them to the product
        $productTaxa = $createProduct->getProductTaxa($productId);

        $product->updateProductTaxa(
            $productTaxa
        );
        $product->addData($createProduct->getData());

        $variant = Variant::create(
            $productId,
            $this->variantRepository->nextReference(),
            $createProduct->getUnitPrice($this->traderConfig->doesPriceInputIncludesVat(), $this->traderConfig->getDefaultCurrency()),
            $createProduct->getSalePrice($this->traderConfig->doesPriceInputIncludesVat(), $this->traderConfig->getDefaultCurrency()),
            $createProduct->getSku()
        );

        $variant->addData($createProduct->getVariantData());
        $variant->showInGrid();

        $product->createVariant($variant);

        $this->productRepository->save($product);

        $this->eventDispatcher->dispatchAll($product->releaseEvents());

        return $productId;
    }

    public function createVariant(CreateVariant $createVariant): VariantId
    {
        $product = $this->productRepository->find($createVariant->getProductId());

        $product->createVariant($variant = Variant::create(
            $product->productId,
            $this->variantRepository->nextReference(),
            $createVariant->getUnitPrice($this->traderConfig->doesPriceInputIncludesVat(), $this->traderConfig->getDefaultCurrency()),
            $createVariant->getSalePrice($this->traderConfig->doesPriceInputIncludesVat(), $this->traderConfig->getDefaultCurrency()),
            $createVariant->getSku(),
        ));

        $variant->addData($createVariant->getData());

        $this->productRepository->save($product);

        $this->eventDispatcher->dispatchAll($product->releaseEvents());

        return $variant->variantId;
    }

    public function updateProductTaxa(UpdateProductTaxa $updateProductTaxa): void
    {
        $product = $this->productRepository->find($updateProductTaxa->getProductId());

        // WRONG, WE WANT TO LIMIT IT FOR ALL THE TAXONOMIES WE ARE UPDATING...
        if (count($updateProductTaxa->getScopedTaxonomyIds())) {
            $tree = $this->taxonTreeRepository->getTreeByTaxonomies(
                array_map(fn ($taxonomyId) => $taxonomyId->get(), $updateProductTaxa->getScopedTaxonomyIds())
            );

            $allTaxonIdsInSameTaxonomy = $tree->flatten()->pluck(fn ($node) => $node->getId());

            // Keep all the taxa that do NOT belong to the taxonomy we are updating.
            $productTaxa = array_filter($product->getProductTaxa(), fn ($taxon) => ! in_array($taxon->taxonId->get(), $allTaxonIdsInSameTaxonomy));

            $newProductTaxa = [...$productTaxa, ...$updateProductTaxa->getProductTaxa()];
        } else {
            $newProductTaxa = $updateProductTaxa->getProductTaxa();
        }

        $taxonomies = $this->taxonomyRepository->findManyByTaxa(
            array_map(fn ($taxon) => $taxon->taxonId->get(), $newProductTaxa)
        );

        $variantTaxonomies = array_filter($taxonomies, fn (Taxonomy $taxonomy) => $taxonomy->getType() == TaxonomyType::variant_property);
        $variantTaxonomyIds = array_map(fn (Taxonomy $taxonomy) => $taxonomy->taxonomyId->get(), $variantTaxonomies);

        // We need to differentiate between standard Product taxa and Variant taxa.
        foreach ($newProductTaxa as $i => $productTaxon) {
            $taxonNode = $this->taxonTreeRepository->findTaxonById($productTaxon->taxonId->get());

            if (in_array($taxonNode->getTaxonomyId(), $variantTaxonomyIds)) {
                $newProductTaxa[$i] = $productTaxon->toVariantProperty();
            }
        }

        $product->updateProductTaxa($newProductTaxa);

        $this->productRepository->save($product);

        $this->eventDispatcher->dispatchAll($product->releaseEvents());
    }

    public function updateProductData(UpdateProductData $updateProductData): void
    {
        $product = $this->productRepository->find($updateProductData->getProductId());

        $product->addData($updateProductData->getData());

        $this->productRepository->save($product);

        $this->eventDispatcher->dispatchAll($product->releaseEvents());
    }

    public function updateVariantTaxa(UpdateVariantTaxa $command): void
    {
        $product = $this->productRepository->find($command->getProductId());
        $variant = $product->findVariant($command->getVariantId());

        $variant->updateVariantTaxa($command->getVariantTaxa());
        $product->updateVariant($variant);

        $this->productRepository->save($product);

        $this->eventDispatcher->dispatchAll($product->releaseEvents());
    }

    public function updateProductPersonalisations(UpdateProductPersonalisations $updateProductPersonalisations): void
    {
        $product = $this->productRepository->find($updateProductPersonalisations->getProductId());
        $personalisations = [];

        foreach ($updateProductPersonalisations->getPersonalisations() as $personalisationItem) {
            $personalisation = Personalisation::create(
                $product->productId,
                $personalisationItem->getPersonalisationId() ?: $product->getNextPersonalisationId(),
                $personalisationItem->getPersonalisationType(),
                $personalisationItem->getData()
            );

            $personalisations[] = $personalisation;
        }

        $product->updatePersonalisations($personalisations);

        $this->productRepository->save($product);

        $this->eventDispatcher->dispatchAll($product->releaseEvents());
    }

    public function deleteProduct(DeleteProduct $deleteProduct): void
    {
        $this->productRepository->delete($deleteProduct->getProductId());

        $this->eventDispatcher->dispatchAll([
            new ProductDeleted($deleteProduct->getProductId()),
        ]);
    }

    public function deleteVariant(DeleteVariant $deleteVariant): void
    {
        $product = $this->productRepository->find($deleteVariant->getProductId());

        $product->deleteVariant($deleteVariant->getVariantId());

        $this->productRepository->save($product);

        $this->eventDispatcher->dispatchAll($product->releaseEvents());
    }
}
