<?php

namespace Thinktomorrow\Trader\Purchase\Cart\Application;

class AddToCart
{
    /** @var CurrentCart */
    private $currentCart;

    /** @var ProductReadRepository */
    private $productReadRepository;

    /** @var CartFactory */
    private $cartItemsFactory;

    public function __construct(CurrentCart $currentCart, ProductReadRepository $productReadRepository, CartItemsFactory $cartItemsFactory)
    {
        $this->currentCart = $currentCart;
        $this->productReadRepository = $productReadRepository;
        $this->cartItemsFactory = $cartItemsFactory;
    }

    public function handle(int $productId, int $quantity)
    {
        if( ! $product = $this->productReadRepository->find($productId)) {
            throw new AddedProductNotFound('No product found for id ['.$productId.'].');
        }

        $cart = $this->currentCart->get();

        $cartItem = $this->cartItemsFactory->createItem($product->id, $product, 0);

        $cart->items()->add($cartItem, $quantity);

        $this->currentCart->save($cart);
    }
}
