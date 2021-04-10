<?php

namespace Thinktomorrow\Trader\Purchase\Cart\Ports;

use Closure;
use Illuminate\Support\Facades\Log;
use Optiphar\Cart\CartReference;
use Optiphar\Cart\Ports\CartModel;
use Optiphar\Cart\Ports\LaravelCookieCartReference;
use Optiphar\Checkout\Application\ConfirmAndClearCartAfterCheckout;
use Optiphar\Orders\OrderRepository;

class LaravelCartMiddleware
{
    /** @var DbCurrentCartSource */
    private $currentCart;

    /** @var LaravelCookieCartReferenceSource */
    private $cookieCartReference;

    public function __construct(DbCurrentCartSource $currentCart, LaravelCookieCartReference $cookieCartReference)
    {
        $this->currentCart = $currentCart;
        $this->cookieCartReference = $cookieCartReference;
    }

    public function handle($request, Closure $next)
    {
        // If current visitor has a cookie cart reference, this is used to retrieve current cart
        if($this->cookieCartReference->exists()){

            /**
             * This is a temp fix to ensure no confirmed carts which are already being
             * handled as orders, are still available as cart.
             */
            if(!$request->routeIs('payment.success') && !$request->routeIs('checkout.confirmed')){
                $order = app(OrderRepository::class)->findByReference($this->cookieCartReference->get());

                // If order already in merchant hands but the cart is still present in the database, we ensure the checkout is completely finished and confirm the cart now
                if($order && $order->inMerchantHands() && CartModel::findByReference(CartReference::fromString($this->cookieCartReference->get())))
                {
                    Log::warning('Cart by reference ['.$this->cookieCartReference->get().'] was still available for customer while already being a paid order. This should not happen. Current cart status: ' . $this->currentCart->get()->state()->get());

                    app(ConfirmAndClearCartAfterCheckout::class)->handle(CartReference::fromString($this->cookieCartReference->get()));

                    $this->share();

                    return $next($request);
                }
            }

            $this->currentCart->setReference(CartReference::fromString($this->cookieCartReference->get()));
        }

        $this->share();

        return $next($request);
    }

    public function share()
    {
        view()->share('cart', $this->currentCart->get());
    }
}
