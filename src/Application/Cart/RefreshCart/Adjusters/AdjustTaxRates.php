<?php

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Application\VatRate\FindVatRateForOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

class AdjustTaxRates implements Adjuster
{
    private VariantForCartRepository $variantForCartRepository;
    private FindVatRateForOrder $findVatRateForOrder;

    public function __construct(VariantForCartRepository $variantForCartRepository, FindVatRateForOrder $findVatRateForOrder)
    {
        $this->variantForCartRepository = $variantForCartRepository;
        $this->findVatRateForOrder = $findVatRateForOrder;
    }

    public function adjust(Order $order): void
    {
        $this->adjustLinePrices($order);
        $this->adjustShippingCosts($order);
        $this->adjustPaymentCosts($order);
    }

    private function adjustLinePrices(Order $order): void
    {
        foreach ($order->getLines() as $line) {

            // Get variant of line for original price
            $variant = $this->variantForCartRepository->findVariantForCart($line->getVariantId());
            $originalVatPercentage = $variant->getSalePrice()->getVatPercentage();

            $vatPercentage = $this->findVatRateForOrder->findForLine($order, $originalVatPercentage);

            $linePrice = $line->getLinePrice();
            $linePrice = $linePrice->changeVatPercentage($vatPercentage);
            $line->updatePrice($linePrice);
        }
    }

    private function adjustShippingCosts(Order $order): void
    {
        foreach ($order->getShippings() as $shipping) {

            $vatPercentage = $this->findVatRateForOrder->findForShippingCost($order);

            $shippingCost = $shipping->getShippingCost();
            $shippingCost = $shippingCost->changeVatPercentage($vatPercentage);
            $shipping->updateCost($shippingCost);
        }
    }

    private function adjustPaymentCosts(Order $order): void
    {
        foreach ($order->getPayments() as $payment) {

            $vatPercentage = $this->findVatRateForOrder->findForPaymentCost($order);

            $paymentCost = $payment->getPaymentCost();
            $paymentCost = $paymentCost->changeVatPercentage($vatPercentage);
            $payment->updateCost($paymentCost);
        }
    }
}
