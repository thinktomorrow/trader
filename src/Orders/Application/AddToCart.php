<?php

namespace Thinktomorrow\Trader\Orders\Application;

use Thinktomorrow\Trader\Catalog\Products\ProductRepository;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\OrderRepository;

class AddToCart
{
    /**
     * @var ProductRepository
     */
    private $productRepository;
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository, ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
        $this->orderRepository = $orderRepository;
    }

    public function handle($variant_id)
    {
        // TODO Get current order or create one. note that abandoned logic is elsewhere. There should be
        // A cronjob running to remove old abandoned orders. This makes it easier for this query since
        // if there is an order found, we can safely use it without extra checking.
        $order = $this->orderRepository->findOrCreateCart();

        $productVariant = $this->productRepository->findVariant($variant_id);

        $order->items()->add(Item::fromPurchasable($productVariant));
    }
}
