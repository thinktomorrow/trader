<?php

declare(strict_types = 1);

namespace Purchase\Cart\Application;

use Purchase\Cart\Ports\CartModel;
use Thinktomorrow\Trader\Purchase\Cart\CartReference;
use Thinktomorrow\Trader\Purchase\Cart\Http\CurrentCart;
use Thinktomorrow\Trader\Purchase\Cart\Events\CartDeleted;
use function Thinktomorrow\Trader\Purchase\Cart\Application\event;

class DeleteCart
{
    /** @var CurrentCart */
    private $currentCart;

    public function __construct(CurrentCart $currentCart)
    {
        $this->currentCart = $currentCart;
    }

    public function handle(CartReference $cartReference)
    {
        $model = CartModel::findByReference($cartReference);

        $model->delete();

        $this->currentCart->clearCachedCart();

        event(new CartDeleted($cartReference));

        return;
    }
}
