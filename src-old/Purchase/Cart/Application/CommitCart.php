<?php

namespace Purchase\Cart\Application;

use Purchase\Cart\Ports\CartModel;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Purchase\Cart\Domain\Events\CartCommitted;
use Thinktomorrow\Trader\Purchase\Cart\CartState;
use Optiphar\Orders\Application\ConvertCartToOrder;
use Thinktomorrow\Trader\Purchase\Cart\Http\CurrentCart;
use function Thinktomorrow\Trader\Purchase\Cart\Application\event;

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
