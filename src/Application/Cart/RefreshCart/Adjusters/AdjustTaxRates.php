<?php

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateRepository;

class AdjustTaxRates implements Adjuster
{
    private VatRateRepository $taxRateProfileRepository;
    private VariantForCartRepository $variantForCartRepository;

    public function __construct(VatRateRepository $taxRateProfileRepository, VariantForCartRepository $variantForCartRepository)
    {
        $this->taxRateProfileRepository = $taxRateProfileRepository;
        $this->variantForCartRepository = $variantForCartRepository;
    }

    public function adjust(Order $order): void
    {
        if (! $billingCountryId = $order->getBillingAddress()?->getAddress()?->countryId) {
            return;
        }

        if (! $taxRateProfile = $this->taxRateProfileRepository->findVatRateForCountry($billingCountryId->get())) {
            return;
        }

        $this->adjustLinePrices($order, $taxRateProfile);
        $this->adjustShippingCosts($order, $taxRateProfile);
        $this->adjustPaymentCosts($order, $taxRateProfile);
    }

    private function adjustLinePrices(Order $order, VatRate $taxRateProfile): void
    {
        foreach ($order->getLines() as $line) {

            // Get variant of line for original price
            $variant = $this->variantForCartRepository->findVariantForCart($line->getVariantId());

            dd($line, $variant);
            if ($double = $taxRateProfile->hasMappingForRate($line->getLinePrice()->getTaxRate())) {
                $linePrice = $line->getLinePrice();
                $linePrice = $linePrice->changeTaxRate($double->getTargetRate());
                $line->updatePrice($linePrice);
            }
        }
    }

    private function adjustShippingCosts(Order $order, VatRate $taxRateProfile): void
    {
        foreach ($order->getShippings() as $shipping) {
            if ($double = $taxRateProfile->hasMappingForRate($shipping->getShippingCost()->getTaxRate())) {
                $shippingCost = $shipping->getShippingCost();
                $shippingCost = $shippingCost->changeTaxRate($double->getTargetRate());
                $shipping->updateCost($shippingCost);
            }
        }
    }

    private function adjustPaymentCosts(Order $order, VatRate $taxRateProfile): void
    {
        foreach ($order->getPayments() as $payment) {
            if ($double = $taxRateProfile->hasMappingForRate($payment->getPaymentCost()->getTaxRate())) {
                $paymentCost = $payment->getPaymentCost();
                $paymentCost = $paymentCost->changeTaxRate($double->getTargetRate());
                $payment->updateCost($paymentCost);
            }
        }
    }
}
