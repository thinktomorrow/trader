<?php declare(strict_types=1);

namespace Purchase\Cart\Domain;

use Money\Money;
use Common\Notes\Note;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Common\Cash\RendersMoney;
use Common\Domain\Taxes\TaxRate;
use Common\Notes\NoteCollection;
use Purchase\Items\Domain\PurchasableItemId;
use Purchase\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Cash;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;
use Purchase\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Percentage;
use Thinktomorrow\Trader\Purchase\Cart\Domain\ProductRead;
use Thinktomorrow\Trader\Purchase\Cart\Domain\ProductStatus;
use function Thinktomorrow\Trader\Purchase\Cart\Domain\app;
use function Thinktomorrow\Trader\Purchase\Cart\Domain\report;
use function Thinktomorrow\Trader\Purchase\Cart\Domain\config;

class DefaultCartItem implements CartItem
{
    use HasMagicAttributes, RendersMoney;

    /** @var string */
    private $id;

    /** @var PurchasableItemId */
    private $purchasableItemId;

    /** @var Money */
    private $salePrice;

    /** @var TaxRate */
    private $taxRate;

    /** @var AppliedDiscountCollection */
    private $discounts;

    /** @var NoteCollection */
    private $notes;

    /** @var int */
    private $quantity;

    /** @var ProductRead */
//    private $product;

    /** @var string */
//    private $priceDescription;

    /** @var Money */
//    private $calculatedTotal;

    /** @var array */
    private $attributes;

    public function __construct(
        string $id,
        PurchasableItemId $purchasableItemId,
        Money $salePrice,
        TaxRate $taxRate,
        AppliedDiscountCollection $discounts,
        NoteCollection $notes,
        array $attributes,
        int $quantity = 1
    )
    {
        $this->id = $id;
        $this->purchasableItemId = $purchasableItemId;
        $this->salePrice = $salePrice;
        $this->taxRate = $taxRate;
        $this->discounts = $discounts;
        $this->notes = $notes;
        $this->attributes = $attributes;

        $this->setQuantity($quantity);
    }

    public function id()
    {
        return $this->attr('data.id');
    }


    public function purchasableItemId(): PurchasableItemId
    {
        return $this->attr('data.product_id');
    }

    /**
     * Indicate this item is added as free product within the
     * context of a free product discount.
     * @return bool
     */
    public function isAddedAsFreeItemDiscount(): bool
    {
        return Str::startsWith($this->id(), 'free');
    }

    public function brandId()
    {
        return $this->attr('data.brand_id');
    }

    public function categoryIds(): array
    {
        return $this->attr('data.category_ids', []);
    }

    public function quantifiedTotal(): Money
    {
        return $this->total()->multiply($this->quantity);
    }

    public function total(): Money
    {
        $total = $this->isTaxApplicable()
            ? $this->totalGross()
            : $this->totalGross()->subtract($this->taxTotal());

        if($total->isNegative()){
            report(new \DomainException('Cart item total dropped under zero ['.$total->getAmount().'] for cart item ['.$this->id().']'));
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

    /**
     * Sales are the price reductions that are on the product level, not on cart level.
     * This is in contrast with 'discounts' which are added according to each cart.
     *
     * @return Money
     */
    public function saleTotal(): Money
    {
        return $this->price()->subtract($this->salePrice());
    }

    public function discountTotal(): Money
    {
        return $this->discounts()->reduce(function($carry, AppliedDiscount $discount){
            return $carry->add($discount->total());
        }, Money::EUR(0));
    }

    public function saleAndDiscountTotal(): Money
    {
        $total = $this->saleTotal()->add($this->discountTotal());

        // Constraint where the sale and discount add up to more than 100% of the salePrice, we'll
        // make sure that the total doesn't surplus the original price.
        if($total->greaterThan($this->price())) {
            report(new \DomainException('CartItem saleAndDiscountTotal forced to equal original price. Reason: It exceeds the original price which should not occur. ['.$total->getAmount().' > '.$this->price()->getAmount().']'));
            return $this->price();
        }

        return $total;
    }

    public function subTotal(): Money
    {
        return $this->salePrice();
    }

    public function taxTotal(): Money
    {
        $nettTotal = Cash::from($this->totalGross())->subtractTaxPercentage($this->taxRate());

        $taxTotal = $this->totalGross()->subtract($nettTotal);

        // In case that the discount makes the total drop under zero,
        // we'll need to address this on the tax total as well.
        if($taxTotal->isNegative()) return Cash::zero();

        return $taxTotal;
    }

    public function quantifiedTotalAsString(): string { return $this->renderMoney($this->quantifiedTotal()); }
    public function totalAsString(): string { return $this->renderMoney($this->total()); }
    public function discountTotalAsString(): string { return $this->renderMoney($this->discountTotal()); }
    public function subTotalAsString(): string { return $this->renderMoney($this->subTotal()); }
    public function taxTotalAsString(): string { return $this->renderMoney($this->taxTotal()); }

    public function taxRate(): Percentage
    {
        return $this->attr('data.taxrate');
    }

    public function taxRateAsPercent(): string
    {
        return $this->renderPercentage($this->taxRate());
    }

    private function isTaxApplicable(): bool
    {
        return !! $this->attr('data.is_tax_applicable', true);
    }

    public function salePrice(): Money
    {
        return $this->salePrice;
    }

    public function price(): Money
    {
        return $this->attr('data.price');
    }

    public function discounts(): array
    {
        return $this->discounts;
    }

    public function addDiscount(AppliedDiscount $discount)
    {
        $this->discounts->push($discount);

        $this->calculatedTotal = null;
    }

    /**
     * Total amount on which the discounts should be calculated.
     *
     * @param array $conditions
     * @return Money
     */
    public function discountableTotal(array $conditions): Money
    {
        // For a cartItem we take the original price - without applied sales - as the
        // base for our discounts. This ensures a nice merge between sales and discounts.
        return $this->product()->priceAsMoney();
    }

    /**
     * Quantity of all whitelisted items. Used by quantity specific
     * discount conditions such as MinimumItems.
     *
     * @param array $conditions
     * @return int
     */
    public function discountableQuantity(array $conditions): int
    {
        return 0;
    }

    public function label(string $locale = null)
    {
        return $this->trans('label', $locale);
    }

    public function notes(): NoteCollection
    {
        return $this->notes;
    }

    public function addNote(Note $note)
    {
        $this->notes[] = $note;
    }

    public function replaceData($key, $value)
    {
        Arr::set($this->data, $key, $value);

        return $this;
    }

    public function promoDescription(string $locale = null)
    {
        return $this->trans('promodescription', $locale);
    }

    public function priceDescription()
    {
        return $this->priceDescription;
    }

    public function setPriceDescription(string $priceDescription)
    {
        $this->priceDescription = $priceDescription;
    }

    public function inStock(): bool
    {
        return ! $this->product->isStatus(ProductStatus::OUT_OF_STOCK);
    }

    public function isAvailable(): bool
    {
        return ! $this->product->isStatus(ProductStatus::NOT_AVAILABLE_ON_MARKET);
    }

    public function hasValidPrice(): bool
    {
        return ! $this->product->isStatus(ProductStatus::INVALID_PRICE);
    }

    public function isVisible(): bool
    {
        return $this->product->isVisible();
    }

    public function isMedicine(): bool
    {
        return $this->product->is('medicin');
    }

    public function quantity()
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity = 1)
    {
        $this->quantity = $quantity;
        $this->sanitizeQuantity();

        return $this;
    }

    private function sanitizeQuantity()
    {
        if ($this->quantity < 0) {
            $this->quantity = 0;
        }
    }

    public function product(): ProductRead
    {
        return $this->product;
    }

    public function toArray(): array
    {
        return array_merge(array_except($this->data, ['product']), [
            'quantity'      => $this->quantity,
            'total'         => (int) $this->total()->getAmount(),
            'discounttotal' => (int) $this->discountTotal()->getAmount(),
            'subtotal'      => (int) $this->subTotal()->getAmount(),
            'taxtotal'      => (int) $this->taxTotal()->getAmount(),
            'saleprice'     => (int) $this->salePrice()->getAmount(),
            'price'         => (int) $this->price()->getAmount(),
            'discounts'     => $this->discounts()->toArray(),
            'taxrate'       => $this->taxRate()->asPercent(),
        ]);
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
