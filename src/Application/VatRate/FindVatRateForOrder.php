<?php

namespace Thinktomorrow\Trader\Application\VatRate;

use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRate;
use Thinktomorrow\Trader\Domain\Model\VatRate\VatRateRepository;
use Thinktomorrow\Trader\TraderConfig;

class FindVatRateForOrder
{
    /** @var array memoized array of vatRates per given OrderId */
    private array $countryVatPerOrder = [];

    private ?VatPercentage $standardPrimaryVatRate = null;

    public function __construct(private TraderConfig $config, private VatExemptionApplication $checkVatExemptionForOrder, private VatRateRepository $vatRateRepository)
    {

    }

    public function findForLine(Order $order, VatPercentage $variantVatPercentage): VatPercentage
    {
        if (! $this->doesOrderHasBillingCountryOtherThanPrimary($order)) {
            return $variantVatPercentage;
        }

        if ($this->checkVatExemptionForOrder->verifyForOrder($order)) {
            return VatPercentage::zero();
        }

        $vatRates = $this->getVatRatesForOrder($order);

        foreach ($vatRates as $vatRate) {
            if ($vatRate->hasBaseRateOf($variantVatPercentage)) {
                return $vatRate->getRate();
            }
        }

        return ($countryVat = $this->getStandardCountryVatForOrder($order))
            ? $countryVat->getRate()
            : $variantVatPercentage;
    }

    /**
     * Shipping cost always uses the standard vat rate.
     */
    public function findForShippingCost(Order $order): VatPercentage
    {
        if ($this->checkVatExemptionForOrder->verifyForOrder($order)) {
            return VatPercentage::zero();
        }

        return $this->getStandardVatPercentageForOrder($order);
    }

    /**
     * Payment cost always uses the standard vat rate.
     */
    public function findForPaymentCost(Order $order): VatPercentage
    {
        if ($this->checkVatExemptionForOrder->verifyForOrder($order)) {
            return VatPercentage::zero();
        }

        return $this->getStandardVatPercentageForOrder($order);
    }

    private function getStandardVatPercentageForOrder(Order $order): VatPercentage
    {
        return ($countryVat = $this->getStandardCountryVatForOrder($order)) ? $countryVat->getRate() : $this->getStandardPrimaryVatRate();
    }

    private function getStandardPrimaryVatRate(): VatPercentage
    {
        return $this->standardPrimaryVatRate ?? $this->standardPrimaryVatRate = $this->vatRateRepository->getStandardPrimaryVatRate();
    }

    private function getStandardCountryVatForOrder(Order $order): ?VatRate
    {
        $countryVatRates = $this->getVatRatesForOrder($order);

        foreach ($countryVatRates as $applicableVatRate) {
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
        if (isset($this->countryVatPerOrder[$order->orderId->get()])) {
            return $this->countryVatPerOrder[$order->orderId->get()];
        }

        if (! $billingCountryId = $order->getBillingAddress()?->getAddress()?->countryId) {
            return $this->countryVatPerOrder[$order->orderId->get()] = [];
        }

        if (! $this->doesOrderHasBillingCountryOtherThanPrimary($order)) {
            return $this->countryVatPerOrder[$order->orderId->get()] = [];
        }

        if (! $countryVatRates = $this->vatRateRepository->getVatRatesForCountry($billingCountryId)) {
            return $this->countryVatPerOrder[$order->orderId->get()] = [];
        }

        return $this->countryVatPerOrder[$order->orderId->get()] = $countryVatRates;
    }

    private function doesOrderHasBillingCountryOtherThanPrimary(Order $order): bool
    {
        if (! $billingCountryId = $order->getBillingAddress()?->getAddress()?->countryId) {
            return false;
        }

        return $billingCountryId->get() != $this->config->getPrimaryVatCountry();
    }

    public function clearMemoizedVatRates(): void
    {
        $this->countryVatPerOrder = [];
        $this->standardPrimaryVatRate = null;
    }
}
