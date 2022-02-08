<?php

namespace Purchase\Cart\Domain\Adjusters;

use Optiphar\Cart\Cart;
use Optiphar\Cart\Adjusters\Adjuster;
use Optiphar\Payments\Costs\PaymentCostPrice;
use Optiphar\Payments\Costs\PaymentCostRepository;

class PaymentCostAdjuster implements Adjuster
{
    /** @var PaymentCostRepository */
    private $paymentCostRepository;

    public function __construct(PaymentCostRepository $paymentCostRepository)
    {
        $this->paymentCostRepository = $paymentCostRepository;
    }

    public function adjust(Cart $cart)
    {
        $paymentCostPrice = $this->findPaymentCost($cart);

        $adjustedCartPayment = $cart->payment()->adjustSubTotal($paymentCostPrice->rate);

        $cart->replacePayment($adjustedCartPayment);
    }

    private function findPaymentCost(Cart $cart): PaymentCostPrice
    {
        if( ! $cart->payment()->hasMethod()) {
            return $this->paymentCostRepository->getDefaultPrice();
        }

        $paymentCostPrice = $this->paymentCostRepository->findPriceForAmount(
            $cart->payment()->method(),
            $cart->subTotal()
        );

        if(!$paymentCostPrice) return $this->paymentCostRepository->getDefaultPrice();

        return $paymentCostPrice;
    }

}
