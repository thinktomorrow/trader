<?php

namespace Thinktomorrow\Trader\Order\Domain;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\ChannelId;
use Thinktomorrow\Trader\Common\Domain\Locale;
use Thinktomorrow\Trader\Common\Notes\HasNotes;
use Thinktomorrow\Trader\Common\State\Stateful;
use Thinktomorrow\Trader\Discounts\Domain\Couponable;
use Thinktomorrow\Trader\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Taxes\TaxRateTotals;

interface Order extends Stateful, Discountable, Couponable, HasNotes
{
    public function getReference(): OrderReference;

    public function getChannel(): ChannelId;

    public function getLocale(): Locale;

    public function getOrderState(): string;

    public function getItems(): OrderProductCollection;

    public function isEmpty(): bool;

    /**
     * The amount of different items.
     * @return int
     */
    public function getSize(): int;

    /**
     * The total quantity of all items combined
     * @return int
     */
    public function getQuantity(): int;

    /**
     * Final total amount including discounts and additional costs.
     * @return Money
     */
    public function getTotal(): Money;

    /**
     * Items total without global discounts or additional shipping or payment costs.
     * @return Money
     */
    public function getSubTotal(): Money;

    /**
     * Combined total of all taxes, including product, shipping or payment ones.
     * @return Money
     */
    public function getTaxTotal(): Money;

    /**
     * Tax totals per rate.
     * @return TaxRateTotals
     */
    public function getTaxTotalPerRate(): TaxRateTotals;

    public function getShipping(): OrderShipping;
    public function replaceShipping(OrderShipping $orderShipping): void;

    public function getPayment(): OrderPayment;
    public function replacePayment(OrderPayment $orderPayment): void;

    public function getCustomer(): OrderCustomer;
    public function replaceCustomer(OrderCustomer $orderCustomer): void;
}
