<?php declare(strict_types = 1);

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

use Money\Money;
use Illuminate\Support\Arr;
use Thinktomorrow\Trader\Find\Channels\ChannelId;
use Thinktomorrow\Trader\Common\Cash\RendersMoney;
use Thinktomorrow\Trader\Common\Domain\Locales\LocaleId;
use Thinktomorrow\Trader\Fulfil\Domain\FulfillableItemId;
use Thinktomorrow\Trader\Purchase\Notes\Domain\Note;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;
use Thinktomorrow\Trader\Common\Domain\Taxes\TaxRateTotals;
use Thinktomorrow\Trader\Purchase\Notes\Domain\NoteCollection;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\AppliedDiscountCollection;

class DefaultCart implements Cart
{
    use HasMagicAttributes, RendersMoney;

    /** @var CartReference */
    private $reference;

    /** @var CartState */
    private $cartState;

    /** @var CartItemCollection */
    private $items;

    /** @var array */
    private $data;

    /** @var Collection */
    private $discounts;

    /** @var Collection */
    private $notes;

    /** @var CartShipping */
    private $cartShipping;

    /** @var CartPayment */
    private $cartPayment;

    /** @var CartCustomer */
    private $cartCustomer;

    public function __construct(
        CartReference $reference,
        CartState $cartState,
        CartItemCollection $items,
        CartShipping $cartShipping,
        CartPayment $cartPayment,
        CartCustomer $cartCustomer,
        AppliedDiscountCollection $discounts,
        NoteCollection $notes,
        array $data)
    {
        $this->reference = $reference;
        $this->cartState = $cartState;
        $this->items = $items;
        $this->cartShipping = $cartShipping;
        $this->cartPayment = $cartPayment;
        $this->cartCustomer = $cartCustomer;
        $this->discounts = $discounts;
        $this->notes = $notes;
        $this->data = $data;
    }

    public function reference(): CartReference
    {
        return $this->reference;
    }

    public function channel(): ChannelId
    {
        return $this->data('channel');
    }

    public function locale(): LocaleId
    {
        return $this->data('locale');
    }

    public function state(): CartState
    {
        return $this->cartState;
    }

    public function items(): CartItemCollection
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    public function size(): int
    {
        return $this->items()->count();
    }

    /**
     * Quantity across all items
     * @return int
     */
    public function quantity(): int
    {
        return $this->items()->quantity();
    }

    public function isBusiness(): bool
    {
        return $this->is('business');
    }

    public function isTaxApplicable(): bool
    {
        return $this->is('tax_applicable', true);
    }

    public function total(): Money
    {
        $total = $this->subTotal()
                    ->add($this->shipping()->total())
                    ->add($this->payment()->total())
                    ->subtract($this->discountTotal());

        // Vital constraint where negative total is not allowed.
        if($total->isNegative()){
            report(new \DomainException('Cart total dropped under zero ['.$total->getAmount().'] for cart reference ['.$this->reference()->get().']'));
            $total = Money::EUR(0);
        }

        return $total;
    }

    public function subTotal(): Money
    {
        return array_reduce($this->items()->all(), function($carry, CartItem $item){
            return $carry->add($item->quantifiedTotal());
        }, Money::EUR(0));
    }

    public function discountTotal(): Money
    {
        return $this->discounts()->reduce(function($carry, AppliedDiscount $discount){
            return $carry->add($discount->total());
        }, Money::EUR(0));
    }

    public function taxTotal(): Money
    {
        return array_reduce($this->taxRates(), function ($carry, $taxRate) {
            return $carry->add($taxRate['tax']);
        }, Cash::make(0));
    }

    public function taxTotalByRate(): TaxRateTotals
    {
        return TaxRateTotals::fromTaxables([
            // All the items
            // shipping cost
            // payment cost
        ]);
    }

    private function discountableCartItemsData(array $conditions): array
    {
        return (new DiscountableCartItemsData())->get($this, $conditions);
    }

    public function discounts(): array
    {
        return $this->discounts;
    }

    public function addDiscount(AppliedDiscount $discount)
    {
        $this->discounts->push($discount);
    }

    /**
     * Total amount on which the discounts should be calculated.
     *
     * @param array $conditions
     * @return Money
     */
    public function discountableTotal(array $conditions): Money
    {
        return $this->discountableCartItemsData($conditions)['quantified_total'];
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
        return $this->discountableCartItemsData($conditions)['quantity'];
    }

    public function enteredCoupon(): ?string
    {
        return $this->attr('data.details.coupon');
    }

    public function enterCoupon(string $coupon)
    {
        $this->data['details']['coupon'] = $coupon;
    }

    public function removeCoupon()
    {
        $this->data['details']['coupon'] = null;
    }

    public function shipping(): CartShipping
    {
        return $this->cartShipping;
    }

    public function replaceShipping(CartShipping $cartShipping)
    {
        $this->cartShipping = $cartShipping;

        return $this;
    }

    public function payment(): CartPayment
    {
        return $this->cartPayment;
    }

    public function replacePayment(CartPayment $cartPayment)
    {
        $this->cartPayment = $cartPayment;

        return $this;
    }

    public function customer(): CartCustomer
    {
        return $this->cartCustomer;
    }

    public function replaceCustomer(CartCustomer $cartCustomer)
    {
        $this->cartCustomer = $cartCustomer;

        return $this;
    }

    public function replaceData($key, $value)
    {
        Arr::set($this->data, $key, $value);

        return $this;
    }

    public function notes(): NoteCollection
    {
        return $this->notes;
    }

    public function addNote(Note $note)
    {
        $this->notes[] = $note;
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

    private function is($key, $default = false): bool
    {
        return (bool) $this->attr('data.details.is_'.$key, $default);
    }

    public function data($key, $default = null)
    {
        return $this->attr('data.'.$key, $default);
    }

    /**
     * Unique reference to the item record
     *
     * @return \Thinktomorrow\Trader\Fulfil\Domain\FulfillableItemId
     */
    public function fulfillableItemId(): FulfillableItemId
    {
        return FulfillableItemId::fromString($this->reference()->get());
    }

    /**
     * All the information required for the fulfillment of this item.
     * This allows to refer to historical accurate item data.
     * e.g. when converting a cart to an order.
     *
     * @return array
     */
    public function fulfillableItemData(): array
    {
        return [
            'totals' => [
                'total'         => (int) $this->total()->getAmount(),
                'discounttotal' => (int) $this->discountTotal()->getAmount(),
                'subtotal'      => (int) $this->subTotal()->getAmount(),
                'taxtotal'      => (int) $this->taxTotal()->getAmount(),
            ],
            'customer'  => $this->customer()->toArray(),
            'items'     => $this->items()->toArray(),
            'shipping'  => $this->shipping()->toArray(),
            'payment'   => $this->payment()->toArray(),
            'details'   => $this->data['details'],
            'discounts' => $this->discounts()->toArray(),
        ];
    }
}
