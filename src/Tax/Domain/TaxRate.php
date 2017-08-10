<?php

namespace Thinktomorrow\Trader\Tax\Domain;

use Assert\Assertion;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Countries\CountryId;

class TaxRate
{
    /**
     * @var TaxId
     */
    private $taxId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Percentage
     */
    private $percentage;

    /**
     * @var array
     */
    private $countryRateOverrides;

    /**
     * @var CountryId
     */
    private $senderCountryId;

    /**
     * @var CountryId
     */
    private $billingCountryId;

    /**
     * @var CountryId
     */
    private $shipmentCountryId;

    /**
     * @var bool
     */
    private $forBusiness;

    public function __construct(TaxId $taxId, string $name, Percentage $percentage, array $countryRateOverrides = [])
    {
        Assertion::allIsInstanceOf($countryRateOverrides, CountryRate::class);

        $this->taxId = $taxId;
        $this->name = $name;
        $this->percentage = $percentage;
        $this->countryRateOverrides = $countryRateOverrides;
        $this->forBusiness = false;
    }

    public function id(): TaxId
    {
        return $this->taxId;
    }

    /**
     * Sender country
     *
     * @param CountryId $countryId
     * @return $this
     */
    public function fromCountry(CountryId $countryId)
    {
        $this->senderCountryId = $countryId;

        return $this;
    }

    /**
     * Customer billing country
     *
     * @param CountryId $countryId
     * @return $this
     */
    public function forBillingCountry(CountryId $countryId)
    {
        $this->billingCountryId = $countryId;

        return $this;
    }

    /**
     * Customer shipping country.
     * This is important for determining the 0% EU tax rule
     *
     * @param CountryId $countryId
     * @return $this
     */
    public function forShipmentCountry(CountryId $countryId)
    {
        $this->shipmentCountryId = $countryId;

        return $this;
    }

    /**
     * flag to indicate tax should be calculated for a valid business customer
     *
     * @return $this
     */
    public function forBusiness()
    {
        $this->forBusiness = true;

        return $this;
    }

    public function get(): Percentage
    {
        if($this->eligibleForTaxExemption()) return Percentage::fromPercent(0);
        
        if(is_null($this->billingCountryId)) return $this->percentage;

        foreach($this->countryRateOverrides as $countryRate)
        {
            if($countryRate->matchesCountry($this->billingCountryId)) return $countryRate->get();
        }

        return $this->percentage;
    }

    private function eligibleForTaxExemption(): bool
    {
        // Valid business outside the sender country receives tax exemption
        // TODO: is this shipment address that needs to be different of billing address?
        if($this->forBusiness)
        {
            if(!$this->billingCountryId || !$this->senderCountryId) return false;

            return ! $this->billingCountryId->equals($this->senderCountryId);
        }

        // TODO: rule for consumers outside europe or in Norway and Swissland also qualify for this.
        // @ref: https://ecom-support.lightspeedhq.com/hc/nl/articles/115005022268-BTW-regels-hoe-werkt-het-precies-
        
        return false;
    }

    public function name()
    {
        return $this->name;
    }
}