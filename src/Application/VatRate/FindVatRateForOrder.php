<?php

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateRepository;

class FindVatRateForOrder
{
    /** @var array memoized array of vatRates per given OrderId */
    private array $vatRatesPerOrder = [];

    private ?VatPercentage $standardPrimaryVatRate = null;

    public function __construct(private VatRateRepository $vatRateRepository)
    {

    }

    public function findForLine(Order $order, VatPercentage $variantVatPercentage): VatPercentage
    {
        $vatRates = $this->getVatRatesForOrder($order);

        foreach ($vatRates as $vatRate) {
            if ($vatRate->hasBaseRateOf($variantVatPercentage)) {
                return $vatRate->findBaseRateOf($variantVatPercentage)->rate;
            }
        }

        return $this->getStandardVatPercentageForOrder($order);
    }

    /**
     * Shipping cost always uses the standard vat rate.
     */
    public function findForShippingCost(Order $order): VatPercentage
    {
        return $this->getStandardVatPercentageForOrder($order);
    }

    /**
     * Payment cost always uses the standard vat rate.
     */
    public function findForPaymentCost(Order $order): VatPercentage
    {
        return $this->getStandardVatPercentageForOrder($order);
    }

    private function getStandardVatPercentageForOrder(Order $order): VatPercentage
    {
        return ($applicableVatRate = $this->getStandardVatRateForOrder($order)) ? $applicableVatRate->getRate() : $this->getStandardPrimaryVatRate();
    }

    private function getStandardPrimaryVatRate(): VatPercentage
    {
        return $this->standardPrimaryVatRate ?? $this->standardPrimaryVatRate = $this->vatRateRepository->getStandardPrimaryVatRate();
    }

    private function getStandardVatRateForOrder(Order $order): ?VatRate
    {
        $applicableVatRates = $this->getVatRatesForOrder($order);

        foreach ($applicableVatRates as $applicableVatRate) {
            if ($applicableVatRate->isStandard()) {
                return $applicableVatRate;
            }
        }

        return null;
    }

    /**
     * @return VatRate[]
     */
    private function getVatRatesForOrder(Order $order): array
    {
        if (isset($this->vatRatesPerOrder[$order->orderId->get()])) {
            return $this->vatRatesPerOrder[$order->orderId->get()];
        }

        if (! $billingCountryId = $order->getBillingAddress()?->getAddress()?->countryId) {
            return [];
        }

        if (! $countryVatRates = $this->vatRateRepository->getVatRatesForCountry($billingCountryId)) {
            return [];
        }

        return $this->vatRatesPerOrder[$order->orderId->get()] = $countryVatRates;
    }
}
