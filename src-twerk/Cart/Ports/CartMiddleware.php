<?php

namespace Thinktomorrow\Trader\Cart\Ports;

use Closure;
use Illuminate\Support\Facades\Log;
use Thinktomorrow\Trader\Order\Domain\OrderReference;

class CartMiddleware
{
    private CurrentCart $currentCart;
    private OrderReferenceCookie $orderReferenceCookie;

    public function __construct(CurrentCart $currentCart, OrderReferenceCookie $orderReferenceCookie)
    {
        $this->currentCart = $currentCart;
        $this->orderReferenceCookie = $orderReferenceCookie;
    }

    public function handle($request, Closure $next)
    {
        // If current visitor has a cookie cart reference, this is used to retrieve current cart
        try {
            if ($this->orderReferenceCookie->exists()) {
                $this->currentCart->setReference(OrderReference::fromString($this->orderReferenceCookie->get()));
            }
        } catch (\Exception $e) {
        }

        // TODO: make sure cart cannot be retrieved when it is already in merchant hands (state check)
//            $order = app(OrderRepository::class)->findByReference($this->orderReferenceCookie->get());

        // If order already in merchant hands but the cart is still present in the database, we ensure the checkout is completely finished and confirm the cart now
//            if($order && $order->inMerchantHands() && CartModel::findByReference(CartReference::fromString($this->orderReferenceCookie->get())))
//            {
//                Log::warning('Cart by reference ['.$this->orderReferenceCookie->get().'] was still available for customer while already being a paid order. This should not happen. Current cart status: ' . $this->currentCart->get()->state()->get());
//
//                app(ConfirmAndClearCartAfterCheckout::class)->handle(CartReference::fromString($this->orderReferenceCookie->get()));
//
//                $this->shareOnAllViews();
//
//                return $next($request);
//            }

//            $this->currentCart->setReference(CartReference::fromString($this->orderReferenceCookie->get()));

        view()->share('cart', $this->currentCart->get());

        return $next($request);
    }
}
