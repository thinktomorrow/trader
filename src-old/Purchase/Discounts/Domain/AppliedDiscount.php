<?php declare(strict_types=1);

namespace Purchase\Discounts\Domain;

use Money\Money;
use Illuminate\Support\Arr;
use Common\Cash\RendersMoney;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Cash;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;
use Thinktomorrow\Trader\Purchase\Cart\Domain\TypeKey;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Percentage;
use Thinktomorrow\Trader\Purchase\Cart\Domain\DiscountId;
use function Thinktomorrow\Trader\Purchase\Discounts\Domain\app;
use function Thinktomorrow\Trader\Purchase\Discounts\Domain\config;

/** An applied cart discount */
class AppliedDiscount
{
    use HasMagicAttributes, RendersMoney;

    /** @var \Purchase\Discounts\Domain\DiscountId */
    private $discountId;

    /** @var TypeKey */
    private $typeKey;

    /** @var Money */
    private $total;

    /** @var Percentage */
    private $taxRate;

    /** @var Money */
    private $baseTotal;

    /** @var Percentage */
    private $percentage;

    /** @var array */
    private $data;

    public function __construct(DiscountId $discountId, TypeKey $typeKey, Money $total, Percentage $taxRate, Money $baseTotal, Percentage $percentage, array $data)
    {
        $this->discountId = $discountId;
        $this->typeKey = $typeKey;
        $this->total = $total;
        $this->taxRate = $taxRate;
        $this->baseTotal = $baseTotal;
        $this->percentage = $percentage;
        $this->data = $data;
    }

    public function id(): DiscountId
    {
        return $this->discountId;
    }

    public function type(): TypeKey
    {
        return $this->typeKey;
    }

    public function total(): Money
    {
        return $this->isTaxApplicable() ? $this->totalGross() : $this->totalGross()->subtract($this->taxTotal());
    }

    public function totalGross(): Money
    {
        return $this->total;
    }

    public function taxRate(): Percentage
    {
        return $this->taxRate;
    }

    public function taxRateAsPercent(): string
    {
        return $this->renderPercentage($this->taxRate());
    }

    private function isTaxApplicable(): bool
    {
        return !! $this->attr('data.is_tax_applicable', true);
    }

    public function taxTotal(): Money
    {
        $nettTotal = Cash::from($this->totalGross())->subtractTaxPercentage($this->taxRate());
        return $this->totalGross()->subtract($nettTotal);
    }

    public function totalAsString(): string { return $this->renderMoney($this->total()); }
    public function taxTotalAsString(): string { return $this->renderMoney($this->taxTotal()); }

    /**
     * Total where the discount is based upon.
     */
    public function baseTotal(): Money
    {
        return $this->baseTotal;
    }

    public function percentage(): Percentage
    {
        return $this->percentage;
    }

    public function percentageAsPercent(): string
    {
        return $this->renderPercentage($this->percentage());
    }

    public function description(): string
    {
        return (string) $this->trans('description');
    }

    public function usesCoupon(): bool
    {
        if(isset($this->data['uses_coupon'])) {
            return $this->data['uses_coupon'];
        }

        return false;
    }

    public function replaceData($key, $value)
    {
        Arr::set($this->data, $key, $value);

        return $this;
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

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'discountid' => $this->discountId->get(),
            'typekey'    => $this->typeKey->get(),
            'total'      => (int) $this->total->getAmount(),
            'taxrate'   => (int) $this->taxRate()->asPercent(),
            'taxtotal'   => (int) $this->taxTotal()->getAmount(),
            'basetotal'  => (int) $this->baseTotal->getAmount(),
            'percentage' => $this->percentage->asPercent(),
            'data'       => $this->data,
        ];
    }
}
