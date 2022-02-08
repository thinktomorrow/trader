<?php

namespace Thinktomorrow\Trader\Cart\Application;

use Optiphar\Orders\Application\ConvertCartToOrder;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\CartState;
use Thinktomorrow\Trader\Purchase\Cart\Events\CartCommitted;
use Thinktomorrow\Trader\Purchase\Cart\Http\CurrentCart;
use Thinktomorrow\Trader\Purchase\Cart\Ports\CartModel;

class CommitCart
{
    /** @var ConvertCartToOrder */
    private $convertCartToOrder;

    /** @var CurrentCart */
    private $currentCart;

    public function __construct(ConvertCartToOrder $convertCartToOrder, CurrentCart $currentCart)
    {
        $this->convertCartToOrder = $convertCartToOrder;
        $this->currentCart = $currentCart;
    }

    /**
     * Save cart as order in database. If the order already exists
     * The order will be updated with the new cart data
     *
     * @param Cart $cart
     */
    public function handle(Cart $cart)
    {
        $order = $this->convertCartToOrder->convert($cart);

        CartModel::findByReference($cart->reference())->update(['state' => CartState::COMMITTED]);

        // Keep track of the order id
        $this->currentCart->save($cart->setOrderId($order->id));

        event(new CartCommitted($cart->reference()));
    }
}
