<?php

namespace Thinktomorrow\Trader\Tax\Domain;

use Assert\Assertion;
use Illuminate\Contracts\Container\Container;
use Thinktomorrow\Trader\Common\Application\ResolvesFromContainer;
use Thinktomorrow\Trader\Common\Config;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Countries\CountryId;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Tax\Domain\Rules\BillingCountryRule;
use Thinktomorrow\Trader\Tax\Domain\Rules\ForeignBusinessRule;

class TaxRate
{
    use ResolvesFromContainer;

    /**
     * @var Container
     */
    private $container;

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
     * @var CountryRate[]
     */
    private $billingCountryRates;

    /**
     * @var Merchant CountryId
     */
    private $merchantCountryId;

    public function __construct(Container $container, TaxId $taxId, string $name, Percentage $percentage, array $billingCountryRates = [])
    {
        Assertion::allIsInstanceOf($billingCountryRates, CountryRate::class);

        $this->container = $container;
        $this->taxId = $taxId;
        $this->name = $name;
        $this->percentage = $percentage;
        $this->billingCountryRates = $billingCountryRates;

        $this->merchantCountryId = CountryId::fromIsoString((new Config())->get('country_id', 'BE'));
    }

    public function id(): TaxId
    {
        return $this->taxId;
    }

    public function name()
    {
        return $this->name;
    }

    public function merchantCountryId(): CountryId
    {
        return $this->merchantCountryId;
    }

    /**
     * Merchant country.
     *
     * @param CountryId $countryId
     *
     * @return $this
     */
    public function setMerchantCountry(CountryId $countryId)
    {
        $this->merchantCountryId = $countryId;

        return $this;
    }

    /**
     * Enforces rates for specific billing countries.
     *
     * @return array
     */
    public function billingCountryRates(): array
    {
        return $this->billingCountryRates;
    }

    /**
     * Get the applicable taxRate based on current context.
     *
     * @param Taxable|null $taxable
     * @param Order|null   $order
     *
     * @return Percentage
     */
    public function get(Taxable $taxable = null, Order $order = null): Percentage
    {
        $percentage = $this->percentage;

        // Todo: how to provide custom behaviour per project? - we could expose these rules to be managed outside in project scope...
        $rulenames = [
            ForeignBusinessRule::class,
            BillingCountryRule::class,
        ];

        foreach ($rulenames as $rulename) {
            $rule = $this->resolve($rulename);

            if ($rule->context($this, $taxable, $order)->applicable()) {
                return $rule->apply($percentage);
            }
        }

        return $percentage;
    }
}
