<?php

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfile;
use Thinktomorrow\Trader\Domain\Model\TaxRateProfile\TaxRateProfileRepository;

class AdjustTaxRates implements Adjuster
{
    private TaxRateProfileRepository $taxRateProfileRepository;

    public function __construct(TaxRateProfileRepository $taxRateProfileRepository)
    {
        $this->taxRateProfileRepository = $taxRateProfileRepository;
    }

    public function adjust(Order $order): void
    {
        if (! $billingCountryId = $order->getBillingAddress()?->getAddress()?->countryId) {
            return;
        }

        if (! $taxRateProfile = $this->taxRateProfileRepository->findTaxRateProfileForCountry($billingCountryId->get())) {
            return;
        }

        $this->adjustLinePrices($order, $taxRateProfile);
        $this->adjustShippingCosts($order, $taxRateProfile);
        $this->adjustPaymentCosts($order, $taxRateProfile);
    }

    private function adjustLinePrices(Order $order, TaxRateProfile $taxRateProfile): void
    {
        foreach ($order->getLines() as $line) {
            if ($double = $taxRateProfile->findTaxRateDoubleByOriginal($line->getLinePrice()->getTaxRate())) {
                $linePrice = $line->getLinePrice();
                $linePrice = $linePrice->changeTaxRate($double->getRate());
                $line->updatePrice($linePrice);
            }
        }
    }

    private function adjustShippingCosts(Order $order, TaxRateProfile $taxRateProfile): void
    {
        foreach ($order->getShippings() as $shipping) {
            if ($double = $taxRateProfile->findTaxRateDoubleByOriginal($shipping->getShippingCost()->getTaxRate())) {
                $shippingCost = $shipping->getShippingCost();
                $shippingCost = $shippingCost->changeTaxRate($double->getRate());
                $shipping->updateCost($shippingCost);
            }
        }
    }

    private function adjustPaymentCosts(Order $order, TaxRateProfile $taxRateProfile): void
    {
        foreach ($order->getPayments() as $payment) {
            if ($double = $taxRateProfile->findTaxRateDoubleByOriginal($payment->getPaymentCost()->getTaxRate())) {
                $paymentCost = $payment->getPaymentCost();
                $paymentCost = $paymentCost->changeTaxRate($double->getRate());
                $payment->updateCost($paymentCost);
            }
        }
    }
}
