<?php

namespace Thinktomorrow\Trader\Cart\Ports;

use Assert\Assertion;
use Thinktomorrow\Trader\Order\Domain\OrderProductRepository;

class CartItemsFactory
{
    private OrderProductRepository $orderProductRepository;

    public function __construct(OrderProductRepository $orderProductRepository)
    {
        $this->orderProductRepository = $orderProductRepository;
    }

    public function create(array $itemValues): CartItems
    {
        // Each entry should contain these keys
        Assertion::allKeyIsset($itemValues, 'id');
        Assertion::allKeyIsset($itemValues, 'product_id');
        Assertion::allKeyIsset($itemValues, 'quantity');

        $productReads = $this->productReadRepository->findMany(array_pluck($itemValues, 'product_id'));

        $items = collect($itemValues)->map(function (array $rawItem) use ($productReads) {
            $productRead = $productReads->first(function ($productRead) use ($rawItem) {
                return (int) $productRead->id === (int) $rawItem['product_id'];
            });

            if (! $productRead) {
                throw new ProductModelNotFound('No product found by id [' . $rawItem['product_id'] . '].');
            }

            return $this->createItem($rawItem['id'], $productRead, $rawItem['quantity']);
        });

        return new CartItems($items->all());
    }

    public function createItem($itemId, ProductRead $productRead, int $quantity): CartItem
    {
        $translations = collect($productRead->attr('data.translations', []));
        foreach ($translations as $locale => $translation) {
            $translations[$locale] = [
                'label' => $translation->label,
                'package' => $translation->package,
            ];
        }

        return new CartItem([
            'id' => $itemId,
            'product_id' => $productRead->id,
            'brand_id' => $productRead->brand_id,
            'category_ids' => $productRead->categories('id'),
            'saleprice' => $productRead->salePriceAsMoney(),
            'price' => $productRead->priceAsMoney(),
            'taxrate' => $productRead->taxRateAsPercentage(),
            'product' => $productRead,
            'translations' => $translations->toArray(),
        ], $quantity);
    }

    public function createItemById($itemId, int $productId, int $quantity)
    {
        $product = $this->productReadRepository->find($productId);

        return $this->createItem($itemId, $product, $quantity);
    }
}
