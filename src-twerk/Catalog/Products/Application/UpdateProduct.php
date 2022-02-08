<?php

namespace Thinktomorrow\Trader\Catalog\Products\Application;

use Illuminate\Contracts\Events\Dispatcher;
use Money\Money;
use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductUpdated;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductRepository;
use Thinktomorrow\Trader\Taxes\TaxRate;

class UpdateProduct
{
    private ProductRepository $productRepository;
    private Dispatcher $eventDispatcher;

    public function __construct(ProductRepository $productRepository, Dispatcher $eventDispatcher)
    {
        $this->productRepository = $productRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function handle(string $productId, bool $isGridProduct, Money $salePrice, Money $unitPrice, TaxRate $taxRate, ?array $optionIds = null, ?array $data = null): void
    {
        $values = [
            'is_grid_product' => $isGridProduct,
            'sale_price' => $salePrice->getAmount(),
            'unit_price' => $unitPrice->getAmount(),
            'tax_rate' => $taxRate->toPercentage()->toInteger(),
            'currency' => $salePrice->getCurrency()->getCode(),
        ];

        if ($optionIds) {
            $values['option_ids'] = $optionIds;
        }

        if ($data) {
            $values['data'] = $data;
        }

        $this->productRepository->save($productId, $values);

        $this->eventDispatcher->dispatch(new ProductUpdated($productId));
    }
}
