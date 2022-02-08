<?php

namespace Purchase\Cart\Application;

use Purchase\Cart\Domain\CartItemFactory;
use Purchase\Cart\Domain\CurrentCartSource;
use Purchase\Items\Domain\PurchasableItemId;
use Thinktomorrow\Trader\Purchase\PurchasableItem;
use Purchase\Items\Domain\PurchasableItemRepository;
use Thinktomorrow\Trader\Purchase\Cart\Application\CurrentCart;
use function Thinktomorrow\Trader\Purchase\Cart\Application\dd;

class AddToCart
{
    /** @var CurrentCart */
    private $currentCartSource;

    /** @var PurchasableItemRepository */
    private $purchasableItemsRepository;

    /** @var CartItemFactory */
    private $cartItemFactory;

    public function __construct(CurrentCartSource $currentCartSource, PurchasableItemRepository $purchasableItemsRepository, CartItemFactory $cartItemFactory)
    {
        $this->currentCartSource = $currentCartSource;
        $this->purchasableItemsRepository = $purchasableItemsRepository;
        $this->cartItemFactory = $cartItemFactory;
    }

    public function handle(PurchasableItemId $purchasableItemId, int $quantity)
    {
        $cart = $this->currentCartSource->get();
        $purchasableItem = $this->purchasableItemsRepository->findById($purchasableItemId, $cart->channel(), $cart->locale());

        $cartItem = $this->cartItemFactory->create(
            $purchasableItem->purchasableItemId()->get(),
            $purchasableItem,
            []
        );

        $cart->items()->add($cartItem, $quantity);
dd($cart->total());
        $this->currentCartSource->save($cart);
    }
}
