<?php

namespace Optiphar\Discounts\Types;

use Money\Money;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\CartDiscount;
use Optiphar\Cashier\Percentage;
use Optiphar\Cashier\TaxRate;
use Optiphar\Discounts\Discount;
use Optiphar\Discounts\DiscountId;
use Optiphar\Discounts\Discountable;
use Optiphar\Discounts\Exceptions\CannotApplyDiscount;
use Optiphar\Promos\Common\Domain\Promo;

class PercentageOffDiscount extends BaseDiscount implements Discount
{
    /** @var Percentage */
    private $percentage;

    public function __construct(DiscountId $id, Percentage $percentage, array $conditions, array $data = [])
    {
        parent::__construct($id, $conditions, $data);

        if($percentage->exceeds100()){
            throw new \InvalidArgumentException('Percentage discount cannot exceed 100%. ' . $percentage->asPercent() . ' is passed.');
        }

        $this->percentage = $percentage;
    }

    public static function fromPromo(Promo $promo, array $conditions, array $data): Discount
    {
        return new static(
            DiscountId::fromString($promo->getId()->get()),
            Percentage::fromPercent($promo->getDiscount()->get()),
            $conditions,
            $data
        );
    }

    public function apply(Cart $cart)
    {
        if (!$this->applicable($cart, $cart)) {
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
            $this->percentage,
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

        $discountAmount = $discountBasePrice->multiply($this->percentage->asFloat());

        return $discountBasePriceMinusDiscounts->lessThanOrEqual($discountAmount)
            ? $discountBasePriceMinusDiscounts
            : $discountAmount;
    }
}
