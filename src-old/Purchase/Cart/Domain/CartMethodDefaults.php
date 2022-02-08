<?php declare(strict_types=1);

namespace Purchase\Cart\Domain;

use Money\Money;
use Illuminate\Support\Arr;
use Common\Domain\Taxes\TaxRate;
use Illuminate\Support\Collection;
use Purchase\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Cash;
use function collect;
use function Thinktomorrow\Trader\Purchase\Cart\Domain\app;
use function Thinktomorrow\Trader\Purchase\Cart\Domain\report;
use function Thinktomorrow\Trader\Purchase\Cart\Domain\config;

trait CartMethodDefaults
{
    /** @var string */
    private $method;

    /** @var Money */
    private $subTotal;

    /** @var Collection */
    private $discounts;

    /** @var TaxRate */
    private $taxRate;

    /** @var array */
    private $data;

    public function __construct(string $method, Money $subTotal, TaxRate $taxRate, array $data)
    {
        $this->method   = $method;
        $this->subTotal = $subTotal;
        $this->taxRate  = $taxRate;
        $this->data     = $data;

        if(!isset($this->data['address'])) {
            $this->data['address'] = [];
        }

        $this->discounts = collect();
    }

    public static function empty(): self
    {
        return new static('', Money::EUR(0), TaxRate::default(), []);
    }


    /**
     * Adjust the subtotal of the shipping cost.
     *
     * @param Money $subTotal
     * @return CartMethodDefaults
     */
    public function adjustSubTotal(Money $subTotal): self
    {
        $method = new static($this->method, $subTotal, $this->taxRate, $this->data);

        foreach($this->discounts as $discount) {
            $method->addDiscount($discount);
        }

        return $method;
    }

    /**
     * Adjust the method
     *
     * @param string $method
     * @return CartMethodDefaults
     */
    public function adjustMethod(string $method): self
    {
        $method = new static($method, $this->subTotal, $this->taxRate, $this->data);

        foreach($this->discounts as $discount) {
            $method->addDiscount($discount);
        }

        return $method;
    }

    /**
     * Adjust the address
     *
     * @param array $address
     * @return CartMethodDefaults
     */
    public function adjustAddress(array $address): self
    {
        return $this->adjustData([
            'address' => $address,
        ]);
    }

    /**
     * Adjust the customer address
     *
     * @param array $customerAddress
     * @return CartMethodDefaults
     */
    public function adjustCustomerAddress(array $customerAddress): self
    {
        return $this->adjustData([
            'customer_address' => $customerAddress,
        ]);
    }

    /**
     * Adjust the data
     *
     * @param array $data
     * @return CartMethodDefaults
     */
    public function adjustData(array $data): self
    {
        $method = new static($this->method, $this->subTotal, $this->taxRate, array_merge($this->data, $data));

        foreach($this->discounts as $discount) {
            $method->addDiscount($discount);
        }

        return $method;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function hasMethod(): bool
    {
        return !!($this->method);
    }

    public function hasAddress(): bool
    {
        return $this->hasOwnAddress() || $this->hasCustomerAddress();
    }

    private function hasOwnAddress(): bool
    {
        return isset(
            $this->data['address'],
            $this->data['address']['countryid'],
            $this->data['address']['street'],
            $this->data['address']['number'],
            $this->data['address']['postal'],
            $this->data['address']['city']
        );
    }

    private function hasCustomerAddress(): bool
    {
        return isset(
            $this->data['customer_address'],
            $this->data['customer_address']['countryid'],
            $this->data['customer_address']['street'],
            $this->data['customer_address']['number'],
            $this->data['customer_address']['postal'],
            $this->data['customer_address']['city']
        );
    }

    public function hasCountry(): bool
    {
        return $this->hasOwnCountry() || $this->hasCustomerCountry();
    }

    private function hasOwnCountry(): bool
    {
        return isset(
            $this->data['address'],
            $this->data['address']['countryid']
        );
    }

    private function hasCustomerCountry(): bool
    {
        return isset(
            $this->data['customer_address'],
            $this->data['customer_address']['countryid']
        );
    }

    public function total(): Money
    {
        $total = $this->isTaxApplicable() ? $this->totalGross() : $this->totalGross()->subtract($this->taxTotal());

        if($total->isNegative()){
            report(new \DomainException('Cart shipping/payment total dropped under zero ['.$total->getAmount().']'));
            $total = Money::EUR(0);
        }

        return $total;
    }

    private function totalGross(): Money
    {
        return $this->subTotal()->subtract($this->discountTotal());
    }

    public function isFree(): bool
    {
        return $this->total()->isZero();
    }

    public function discountTotal(): Money {

        return $this->discounts()->reduce(function($carry, AppliedDiscount $discount){
            return $carry->add($discount->total());
        }, Money::EUR(0));
    }

    public function subTotal(): Money
    {
        return $this->subTotal;
    }

    public function taxRate(): TaxRate
    {
        return $this->taxRate;
    }

    public function taxRateAsPercent(): string
    {
        return $this->renderPercentage($this->taxRate());
    }

    public function taxTotal(): Money
    {
        $nettTotal = Cash::from($this->totalGross())->subtractTaxPercentage($this->taxRate());
        return $this->totalGross()->subtract($nettTotal);
    }

    private function isTaxApplicable(): bool
    {
        return !! $this->attr('data.is_tax_applicable', true);
    }

    public function totalAsString(): string { return $this->renderMoney($this->total()); }
    public function discountTotalAsString(): string { return $this->renderMoney($this->discountTotal()); }
    public function subTotalAsString(): string { return $this->renderMoney($this->subTotal()); }
    public function taxTotalAsString(): string { return $this->renderMoney($this->taxTotal()); }

    /**
     * Total amount on which the discounts should be calculated.
     *
     * @param array $conditions
     * @return Money
     */
    public function discountableTotal(array $conditions): Money
    {
        return $this->subTotal();
    }

    public function discountableQuantity(array $conditions): int
    {
        return 1;
    }

    public function discounts(): array
    {
        return $this->discounts;
    }

    public function addDiscount(AppliedDiscount $discount)
    {
        $this->discounts->push($discount);
    }

    public function replaceData($key, $value)
    {
        Arr::set($this->data, $key, $value);

        return $this;
    }

    public function addressValidVat() { return $this->address('valid_vat', false);}
    public function addressVat() { return $this->address('vatid');}
    public function addressSalutation() { return $this->address('salutation');}
    public function addressCompany() { return $this->address('company');}
    public function addressFirstname() { return $this->address('firstname');}
    public function addressLastname() { return $this->address('lastname');}
    public function addressStreet() { return $this->address('street');}
    public function addressNumber() { return $this->address('number');}
    public function addressBus() { return $this->address('bus');}
    public function addressPostal() { return $this->address('postal');}
    public function addressCity() { return $this->address('city');}
    public function addressCountryId() { return $this->address('countryid');}
    public function addressCountry(string $locale = null) {
        return $this->trans('country', $locale, true, 'data.address.translations', $this->trans('country', $locale, true, 'data.customer-address.translations') );
    }

    /**
     * If no address is set yet, the customer address is used as a default.
     * @param $key
     * @param null $default
     * @return mixed
     */
    private function address($key, $default = null)
    {
        return $this->attr('data.address.'.$key,  $this->attr('data.customer_address.'.$key, $default) );
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'total' => (int) $this->total()->getAmount(),
            'discounttotal' => (int) $this->discountTotal()->getAmount(),
            'subtotal' => (int) $this->subTotal()->getAmount(),
            'taxtotal' => (int) $this->taxTotal()->getAmount(),
            'discounts' => $this->discounts()->toArray(),
            'taxrate' => $this->taxRate()->asPercent(),
            'address' => $this->data['address'],
        ];
    }

    /*
     * Retrieve a localized value. By default the current application locale
     * is used to decide which localized value to take. If not present,
     * the value of the fallback locale will be attempted as well.
     */
    private function trans(string $key, string $locale = null, $use_fallback = true, $baseKey = 'data.translations', $default = null)
    {
        if(!$locale) $locale = app()->getLocale();

        if($use_fallback){
            $default = $this->attr($baseKey.'.'.config('app.fallback_locale').'.'.$key, $default);
        }

        return $this->attr("$baseKey.$locale.$key", $default);
    }
}
