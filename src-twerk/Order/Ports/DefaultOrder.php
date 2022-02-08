<?php declare(strict_types = 1);

namespace Thinktomorrow\Trader\Order\Ports;

use Money\Money;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Order\Domain\OrderState;
use Thinktomorrow\Trader\Common\Cash\RendersMoney;
use Thinktomorrow\Trader\Common\Domain\ChannelId;
use Thinktomorrow\Trader\Common\Domain\Locale;
use Thinktomorrow\Trader\Common\Notes\Note;
use Thinktomorrow\Trader\Common\Notes\NoteCollection;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Discounts\Domain\DiscountableOrderProductTotals;
use Thinktomorrow\Trader\Order\Domain\Order as OrderContract;
use Thinktomorrow\Trader\Order\Domain\OrderCustomer;
use Thinktomorrow\Trader\Order\Domain\OrderPayment;
use Thinktomorrow\Trader\Order\Domain\OrderProduct as OrderProductContract;
use Thinktomorrow\Trader\Order\Domain\OrderProductCollection;
use Thinktomorrow\Trader\Order\Domain\OrderReference;
use Thinktomorrow\Trader\Order\Domain\OrderShipping;
use Thinktomorrow\Trader\Taxes\TaxRateTotals;
use function report;
use function data_set;
use function data_get;

class DefaultOrder implements OrderContract
{
    use RendersMoney;

    private OrderReference $reference;
    private string $orderState;
    private OrderProductCollection $items;
    private OrderShipping $orderShipping;
    private OrderPayment $orderPayment;
    private OrderCustomer $orderCustomer;
    private AppliedDiscountCollection $discounts;
    private NoteCollection $notes;
    private array $data;

    public function __construct(
        OrderReference $reference,
        string $orderState,
        OrderProductCollection $items,
        OrderShipping $orderShipping,
        OrderPayment $orderPayment,
        OrderCustomer $orderCustomer,
        AppliedDiscountCollection $discounts,
        NoteCollection $notes,
        array $data
    ) {
        $this->reference = $reference;
        $this->orderState = $orderState;
        $this->items = $items;
        $this->orderShipping = $orderShipping;
        $this->orderPayment = $orderPayment;
        $this->orderCustomer = $orderCustomer;
        $this->discounts = $discounts;
        $this->notes = $notes;
        $this->data = $data;
    }

    public function getReference(): OrderReference
    {
        return $this->reference;
    }

    public function getChannel(): ChannelId
    {
        return $this->data('channel');
    }

    public function getLocale(): Locale
    {
        return $this->data('locale');
    }

    public function getOrderState(): string
    {
        return $this->orderState;
    }

    public function getState(string $key): string
    {
        return $this->orderState;
    }

    public function changeState(string $key, $state): void
    {
        $this->orderState = $state;
    }

    public function getItems(): OrderProductCollection
    {
        return $this->items;
    }

    public function isEmpty(): bool
    {
        return $this->getItems()->isEmpty();
    }

    public function getSize(): int
    {
        return $this->getItems()->count();
    }

    /**
     * Quantity across all items
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->getItems()->quantity();
    }

    public function getTotal(): Money
    {
        $total = $this->getSubTotal()
            ->add($this->getShipping()->getTotal())
            ->add($this->getPayment()->getTotal())
            ->subtract($this->getDiscountTotal());

        // Vital constraint where negative total is not allowed.
        if ($total->isNegative()) {
            report(new \DomainException('Cart total dropped under zero ['.$total->getAmount().'] for cart reference ['.$this->reference()->get().']'));
            $total = Money::EUR(0);
        }

        return $total;
    }

    public function getSubTotal(): Money
    {
        return array_reduce($this->getItems()->all(), function ($carry, OrderProductContract $item) {
            return $carry->add($item->getTotal());
        }, Money::EUR(0));
    }

    public function getDiscountTotal(): Money
    {
        return array_reduce($this->getDiscounts()->all(), function ($carry, AppliedDiscount $discount) {
            return $carry->add($discount->getTotal());
        }, Money::EUR(0));
    }

    public function getTaxTotal(): Money
    {
        return array_reduce($this->getTaxTotalPerRate()->get(), function ($carry, $taxRate) {
            return $carry->add($taxRate['tax']);
        }, Cash::zero());
    }

    public function getTaxTotalPerRate(): TaxRateTotals
    {
        return TaxRateTotals::fromTaxables([
            $this->getShipping(),
            $this->getPayment(),
            ...$this->getItems()->all(),
        ]);
    }

    private function getDiscountableCartItemsData(array $conditions): array
    {
        return (new DiscountableOrderProductTotals())->get($this, $conditions);
    }

    public function getDiscounts(): AppliedDiscountCollection
    {
        return $this->discounts;
    }

    public function addDiscount(AppliedDiscount $discount)
    {
        $this->discounts->add($discount);
    }

    /**
     * Total amount on which the discounts should be calculated.
     *
     * @param array $conditions
     * @return Money
     */
    public function getDiscountableTotal(array $conditions): Money
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
    public function getDiscountableQuantity(array $conditions): int
    {
        return $this->getDiscountableCartItemsData($conditions)['quantity'];
    }

    public function getCoupon(): ?string
    {
        return $this->getData('coupon');
    }

    public function enterCoupon(string $coupon): void
    {
        $this->setData('coupon', $coupon);
    }

    public function removeCoupon(): void
    {
        $this->data['details']['coupon'] = null;
    }

    public function getShipping(): OrderShipping
    {
        return $this->orderShipping;
    }

    public function replaceShipping(OrderShipping $cartShipping): void
    {
        $this->orderShipping = $cartShipping;
    }

    public function getPayment(): OrderPayment
    {
        return $this->orderPayment;
    }

    public function replacePayment(OrderPayment $cartPayment): void
    {
        $this->orderPayment = $cartPayment;
    }

    public function getCustomer(): OrderCustomer
    {
        return $this->orderCustomer;
    }

    public function replaceCustomer(OrderCustomer $cartCustomer): void
    {
        $this->orderCustomer = $cartCustomer;
    }

    public function notes(): NoteCollection
    {
        return $this->notes;
    }

    public function addNote(Note $note)
    {
        $this->notes[] = $note;
    }

//    public function replaceData($key, $value)
//    {
//        Arr::set($this->data, $key, $value);
//
//        return $this;
//    }

    /*
     * Retrieve a localized value. By default the current application locale
     * is used to decide which localized value to take. If not present,
     * the value of the fallback locale will be attempted as well.
     */
//    private function trans(string $key, string $locale = null, $use_fallback = true, $baseKey = 'data.translations', $default = null)
//    {
//        if(!$locale) $locale = app()->getLocale();
//
//        if($use_fallback){
//            $default = $this->attr($baseKey.'.'.config('app.fallback_locale').'.'.$key, $default);
//        }
//
//        return $this->attr("$baseKey.$locale.$key", $default);
//    }

//    private function is($key, $default = false): bool
//    {
//        return (bool) $this->attr('data.details.is_'.$key, $default);
//    }

    private function getData($key, $default = null)
    {
        return data_get($this->data, $key, $default);
    }

    private function setData($key, $value)
    {
        return data_set($this->data, $key, $value);
    }

    /**
     * All the information required for the fulfillment of this item.
     * This allows to refer to historical accurate item data.
     * e.g. when converting a cart to an order.
     *
     * @return array
     */
    public function toRecord(): array
    {
        return [
            'totals' => [
                'total' => (int) $this->getTotal()->getAmount(),
                'discounttotal' => (int) $this->getDiscountTotal()->getAmount(),
                'subtotal' => (int) $this->getSubTotal()->getAmount(),
                'taxtotal' => (int) $this->getTaxTotal()->getAmount(),
            ],
            'customer' => $this->getCustomer()->toArray(),
            'items' => $this->getItems()->toArray(),
            'shipping' => $this->getShipping()->toArray(),
            'payment' => $this->getPayment()->toArray(),
            'details' => $this->data['details'],
            'discounts' => $this->getDiscounts()->toArray(),
        ];
    }
}
