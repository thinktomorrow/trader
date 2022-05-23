<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product;

use Thinktomorrow\Trader\TraderConfig;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Common\Event\EventDispatcher;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductDeleted;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductTaxa;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductData;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductOptions;
use Thinktomorrow\Trader\Application\Product\UpdateVariant\UpdateVariantOptionValues;

class ProductApplication
{
    private TraderConfig $traderConfig;
    private EventDispatcher $eventDispatcher;
    private ProductRepository $productRepository;
    private VariantRepository $variantRepository;

    public function __construct(TraderConfig $traderConfig, EventDispatcher $eventDispatcher, ProductRepository $productRepository, VariantRepository $variantRepository)
    {
        $this->traderConfig = $traderConfig;
        $this->eventDispatcher = $eventDispatcher;
        $this->productRepository = $productRepository;
        $this->variantRepository = $variantRepository;
    }

    public function createProduct(CreateProduct $createProduct): ProductId
    {
        $productId = $this->productRepository->nextReference();

        $product = Product::create($productId);

        $product->updateTaxonIds($createProduct->getTaxonIds());
        $product->addData($createProduct->getData());

        $product->createVariant(Variant::create(
            $productId,
            $this->variantRepository->nextReference(),
            $createProduct->getUnitPrice($this->traderConfig->doesPriceInputIncludesVat(), $this->traderConfig->getDefaultCurrency()),
            $createProduct->getSalePrice($this->traderConfig->doesPriceInputIncludesVat(), $this->traderConfig->getDefaultCurrency()),
        ));

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
        ));

        $variant->addData($createVariant->getData());

        $this->productRepository->save($product);

        $this->eventDispatcher->dispatchAll($product->releaseEvents());

        return $variant->variantId;
    }

    public function updateProductTaxa(UpdateProductTaxa $updateProductTaxa): void
    {
        $product = $this->productRepository->find($updateProductTaxa->getProductId());

        $product->updateTaxonIds($updateProductTaxa->getTaxonIds());

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

    public function updateProductOptions(UpdateProductOptions $updateProductOptions): void
    {
        $product = $this->productRepository->find($updateProductOptions->getProductId());
        $options = [];

        foreach ($updateProductOptions->getOptions() as $optionItem) {
            $option = Option::create($product->productId, $optionItem->getOptionId() ?: $product->getNextOptionId(), $optionItem->getData());

            $option->updateOptionValues(array_map(function ($value) use ($option) {
                return OptionValue::create(
                    $option->optionId,
                    $value->getOptionValueId() ?: $option->getNextOptionValueId(),
                    $value->getData(),
                );
            }, $optionItem->getValues()));

            $options[] = $option;
        }

        $product->updateOptions($options);

        $this->productRepository->save($product);

        $this->eventDispatcher->dispatchAll($product->releaseEvents());
    }

    public function updateVariantOptionValues(UpdateVariantOptionValues $updateVariantOptionValues): void
    {
        $product = $this->productRepository->find($updateVariantOptionValues->getProductId());

        $variant = $product->findVariant($updateVariantOptionValues->getVariantId());
        $variant->updateOptionValueIds($updateVariantOptionValues->getOptionValueIds());
        $product->updateVariant($variant);

        $this->productRepository->save($product);

        $this->eventDispatcher->dispatchAll($product->releaseEvents());
    }

    public function deleteProduct(DeleteProduct $deleteProduct): void
    {
        $this->productRepository->delete($deleteProduct->getProductId());

        $this->eventDispatcher->dispatchAll([
            new ProductDeleted($deleteProduct->getProductId())
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
