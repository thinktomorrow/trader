<?php

namespace Thinktomorrow\Trader\Catalog\Products\Application;

use Thinktomorrow\Trader\Catalog\Products\Domain\ProductState;
use Illuminate\Contracts\Events\Dispatcher;
use Money\Money;
use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductCreated;
use Thinktomorrow\Trader\Catalog\Products\Domain\Product;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductRepository;
use Thinktomorrow\Trader\Taxes\TaxRate;

class CreateProduct
{
    private ProductRepository $productRepository;
    private Dispatcher $eventDispatcher;

    public function __construct(ProductRepository $productRepository, Dispatcher $eventDispatcher)
    {
        $this->productRepository = $productRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handle(string $productGroupId, bool $isGridProduct, Money $salePrice, Money $unitPrice, TaxRate $taxRate, array $optionIds, array $data): Product
    {
        $product = $this->productRepository->create([
            'productgroup_id' => $productGroupId,
            'state' => ProductState::AVAILABLE,
            'is_grid_product' => $isGridProduct,
            'sale_price' => $salePrice->getAmount(),
            'unit_price' => $unitPrice->getAmount(),
            'tax_rate' => $taxRate->toPercentage()->toInteger(),
            'currency' => $salePrice->getCurrency()->getCode(),
            'option_ids' => $optionIds,
            'data' => $data,
        ]);

        $this->eventDispatcher->dispatch(new ProductCreated($product->getId()));

        return $product;
    }
}
