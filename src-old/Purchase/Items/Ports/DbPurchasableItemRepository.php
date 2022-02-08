<?php

namespace Purchase\Items\Ports;

use Find\Channels\ChannelId;
use Find\Catalog\Domain\ProductId;
use Common\Domain\Locales\LocaleId;
use Find\Catalog\Domain\ProductRepository;
use Purchase\Items\Domain\PurchasableItem;
use Purchase\Items\Domain\PurchasableItemId;
use Find\Catalog\Domain\Exceptions\ProductNotFound;
use Purchase\Items\Domain\PurchasableItemRepository;
use Purchase\Cart\Domain\Exceptions\AddedItemNotFound;

class DbPurchasableItemRepository implements PurchasableItemRepository
{
    /** @var ProductRepository */
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function findById(PurchasableItemId $purchasableItemId, ChannelId $channel, LocaleId $locale): PurchasableItem
    {
        // TODO: exceptions for specific cases like:
        // - product not found, no longer available
        // - product out of stock (no longer buyable)
        try {
            /*
             * The default assumptions is that all purchasable items refer to a product.
             * In the specific case that the application requests for multiple purchasables
             * you can customize this logic here by making your own repository class.
             */
            $productId = ProductId::fromString($purchasableItemId->get());

            return $this->productRepository->findById($productId, $channel, $locale);
        } catch (ProductNotFound $e) {
            throw new AddedItemNotFound('No purchasable item found for id [' . $purchasableItemId->get() . '].');
        }
    }
}
