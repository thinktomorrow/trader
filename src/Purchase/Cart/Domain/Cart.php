<?php

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

use Money\Money;
use Thinktomorrow\Trader\Find\Channels\ChannelId;
use Thinktomorrow\Trader\Fulfil\Domain\FulfillableItem;
use Thinktomorrow\Trader\Common\Domain\Locales\LocaleId;
use Thinktomorrow\Trader\Common\Domain\Taxes\TaxRateTotals;
use Thinktomorrow\Trader\Common\Notes\CarriesNotes;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\Couponable;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\Discountable;

interface Cart extends Discountable, Couponable, CarriesNotes, FulfillableItem
{
    public function reference(): CartReference;

    public function channel(): ChannelId;

    public function locale(): LocaleId;

    public function state(): CartState;

    public function items(): CartItemCollection;

    public function isEmpty(): bool;

    /**
     * The amount of different items.
     * @return int
     */
    public function size(): int;

    /**
     * The total quantity of all items combined
     * @return int
     */
    public function quantity(): int;

    /**
     * Indicates if this purchase is made by a business.
     * @return bool
     */
    public function isBusiness(): bool;

    /**
     * Indicates if this purchase requires VAT or not.
     * @return bool
     */
    public function isTaxApplicable(): bool;

    /**
     * Final total amount including discounts and additional costs.
     * @return Money
     */
    public function total(): Money;

    /**
     * Items total without global discounts or additional costs.
     * @return Money
     */
    public function subTotal(): Money;

    /**
     * Entire total of all taxes, combined over different rates.
     * @return Money
     */
    public function taxTotal(): Money;

    public function taxTotalByRate(): TaxRateTotals;

    public function shipping(): CartShipping;

    public function replaceShipping(CartShipping $cartShipping);

    public function payment(): CartPayment;

    public function replacePayment(CartPayment $cartPayment);

    public function customer(): CartCustomer;

    public function replaceCustomer(CartCustomer $cartCustomer);

    public function data($key, $default = null);

    public function replaceData($key, $value);
}
