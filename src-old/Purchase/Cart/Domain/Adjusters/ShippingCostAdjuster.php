<?php

namespace Purchase\Cart\Domain\Adjusters;

use Optiphar\Cart\Cart;
use Optiphar\Cart\Adjusters\Adjuster;
use Optiphar\Deliveries\Costs\DeliveryCostPrice;
use Optiphar\Deliveries\Costs\DeliveryCostRepository;

class ShippingCostAdjuster implements Adjuster
{
    /** @var DeliveryCostRepository */
    private $deliveryCostRepository;

    public function __construct(DeliveryCostRepository $deliveryCostRepository)
    {
        $this->deliveryCostRepository = $deliveryCostRepository;
    }

    public function adjust(Cart $cart)
    {
        $deliveryCostPrice = $this->findDeliveryCost($cart);

        $adjustedCartShipping = $cart->shipping()->adjustSubTotal($deliveryCostPrice->rate);

        $cart->replaceShipping($adjustedCartShipping);
    }

    private function findDeliveryCost(Cart $cart): DeliveryCostPrice
    {
        // If no delivery type or address is set, we presume the default bpost_home / BE
        if( ! $cart->shipping()->hasMethod() || ! $cart->shipping()->hasCountry()) {
            return $this->deliveryCostRepository->getDefaultPrice();
        }

        // The amount where the delivery cost should be calculated upon, is the subtotal.
        $deliveryCostPrice = $this->deliveryCostRepository->findPriceForAmount(
            $cart->shipping()->method(),
            $cart->shipping()->addressCountryId(),
            $cart->subTotal()
        );

        if(!$deliveryCostPrice) return $this->deliveryCostRepository->getDefaultPrice();

        return $deliveryCostPrice;
    }

}
