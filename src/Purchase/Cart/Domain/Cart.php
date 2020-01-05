<?php

declare(strict_types = 1);

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

use Money\Money;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Common\Domain\Cash\RendersMoney;
use Thinktomorrow\MagicAttributes\HasMagicAttributes;

class Cart
{
    use HasMagicAttributes, RendersMoney;

    /** @var CartReference */
    private $reference;

    /** @var CartItems */
    private $items;

    /** @var array */
    private $data;

    /** @var Collection */
    private $discounts;

    /** @var Collection */
    private $notes;

    private function __construct(CartReference $reference, CartItems $items, array $data)
    {
        $this->reference = $reference;
        $this->items = $items;
        $this->data = $data;

        $this->discounts = $data['discounts'] ?? collect();
        $this->notes = $data['notes'] ?? new CartNotes();
    }

    public static function fromData(CartReference $reference, CartItems $items, array $data)
    {
        return new static($reference, $items, $data);
    }

    public static function empty(CartReference $reference)
    {
        return new static($reference, new CartItems(), [
            'state' => CartState::PENDING,
            'details' => [],
        ]);
    }

    public function state(): CartState
    {
        return CartState::fromString($this->attr('data.state'));
    }

    public function reference(): CartReference
    {
        return $this->reference;
    }

    /**
     * When the cart has state of committed, there is already an order created of this cart
     * Here we provide the order id as reference to the order record in database.
     *
     * @return int|null
     */
    public function orderId(): ?int
    {
        return $this->attr('data.details.order_id');
    }

    public function setOrderId(int $orderId)
    {
        $this->data['details']['order_id'] = $orderId;

        return $this;
    }

    public function items(): CartItems
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
        return $this->items()->reduce(function($carry, CartItem $item){
            return $carry->add($item->quantifiedTotal());
        }, Money::EUR(0));
    }

    public function discountTotal(): Money
    {
        return $this->discounts()->reduce(function($carry, CartDiscount $discount){
            return $carry->add($discount->total());
        }, Money::EUR(0));
    }

    public function taxTotal(): Money
    {
        return array_reduce($this->taxRates(), function ($carry, $taxRate) {
            return $carry->add($taxRate['tax']);
        }, Cash::make(0));
    }

    public function totalAsString(): string { return $this->renderMoney($this->total()); }
    public function discountTotalAsString(): string { return $this->renderMoney($this->discountTotal()); }
    public function subTotalAsString(): string { return $this->renderMoney($this->subTotal()); }
    public function taxTotalAsString(): string { return $this->renderMoney($this->taxTotal()); }

    public function taxRates(): array
    {
        return (new SumOfTaxes())->forCart($this);
    }

    public function discountBasePriceAsMoney(array $conditions): Money
    {
        return $this->discountableCartItemsData($conditions)['quantified_total'];
    }

    /**
     * Quantity of all whitelisted products. As usable by quantity specific
     * discount conditions such as MinimumItems.
     *
     * @param array $conditions
     * @return int
     */
    public function discountableCartItemsQuantity(array $conditions): int
    {
        return $this->discountableCartItemsData($conditions)['quantity'];
    }

    private function discountableCartItemsData(array $conditions): array
    {
        return (new DiscountableCartItemsData())->get($this, $conditions);
    }

    public function discountTotalAsMoney(): Money
    {
        return $this->discountTotal();
    }

    public function discounts(): Collection
    {
        return $this->discounts;
    }

    public function addDiscount(CartDiscount $discount)
    {
        $this->discounts->push($discount);
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
        return $this->attr('data.shipping', CartShipping::empty());
    }

    public function replaceShipping(CartShipping $cartShipping)
    {
        $this->data['shipping'] = $cartShipping;

        return $this;
    }

    public function payment()
    {
        return $this->attr('data.payment', CartPayment::empty());
    }

    public function replacePayment(CartPayment $cartPayment)
    {
        $this->data['payment'] = $cartPayment;

        return $this;
    }

    public function customer()
    {
        return $this->attr('data.customer', CartCustomer::empty());
    }

    public function replaceCustomer(CartCustomer $cartCustomer)
    {
        $this->data['customer'] = $cartCustomer;

        return $this;
    }

    public function replaceData($key, $value)
    {
        Arr::set($this->data, $key, $value);

        return $this;
    }

    public function notes(): CartNotes
    {
        return $this->notes;
    }

    public function addNote(CartNote $note)
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

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
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
