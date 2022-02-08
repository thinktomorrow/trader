<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Money\Money;
use Optiphar\Cart\Cart;
use Optiphar\Cart\CartDiscount;
use Optiphar\Cashier\Cash;
use Optiphar\Cashier\TaxRate;
use Optiphar\Promos\Common\Domain\Promo;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\Exceptions\CannotApplyDiscount;

class FixedAmountOffDiscount extends BaseDiscount implements Discount
{
    /** @var Money */
    private $amount;

    public function __construct(DiscountId $id, Money $amount, array $conditions, array $data = [])
    {
        parent::__construct($id, $conditions, $data);

        if ($amount->isNegative()) {
            throw new \InvalidArgumentException('FixedAmountOffDiscount cannot be negative. ' . $amount->getAmount() . ' is passed.');
        }

        $this->amount = $amount;
    }

    public static function fromPromo(Promo $promo, array $conditions, array $data): Discount
    {
        return new static(
            DiscountId::fromString($promo->getId()->get()),
            $promo->getDiscount()->gross(),
            $conditions,
            $data
        );
    }

    public function apply(Cart $cart)
    {
        if (! $this->applicable($cart, $cart)) {
            throw new CannotApplyDiscount('Discount cannot be applied. One or more conditions have failed.');
        }

        $discountBasePrice = $cart->discountBasePriceAsMoney($this->conditions);
        $discountAmount = $this->discountAmount($cart, $cart);

        $cart->addDiscount(new CartDiscount(
            $this->id,
            TypeKey::fromDiscount($this),
            $discountAmount,
            TaxRate::default(),
            $discountBasePrice,
            Cash::from($discountAmount)->asPercentage($discountBasePrice),
            $this->data
        ));
    }

    public function discountAmountTotal(Cart $cart): Money
    {
        return $this->discountAmount($cart, $cart);
    }

    public function discountAmount(Cart $cart, Discountable $eligibleForDiscount): Money
    {
        $discountBasePrice = $eligibleForDiscount->discountBasePriceAsMoney($this->conditions);
        $discountBasePriceMinusDiscounts = $discountBasePrice->subtract($eligibleForDiscount->discountTotalAsMoney());

        return $discountBasePriceMinusDiscounts->lessThanOrEqual($this->amount)
            ? $discountBasePriceMinusDiscounts
            : $this->amount;
    }
}
