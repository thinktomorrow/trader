<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Money\Money;
use Optiphar\Cashier\Cash;
use Optiphar\Cashier\Percentage;
use Optiphar\Cashier\TaxRate;
use Optiphar\Promos\Common\Domain\Promo;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\CartDiscount;
use Thinktomorrow\Trader\Purchase\Cart\CartItem;
use Thinktomorrow\Trader\Purchase\Cart\CartNote;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\Discountable;
use Thinktomorrow\Trader\Purchase\Discounts\Domain\DiscountId;

class CheapestProductPercentageOffDiscount extends BaseDiscount implements Discount
{
    /** @var Percentage */
    private $percentage;

    public function __construct(DiscountId $id, Percentage $percentage, array $conditions, array $data = [])
    {
        parent::__construct($id, $conditions, $data);

        if ($percentage->exceeds100()) {
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

    /**
     * Check if the discount is somehow applicable to the cart or a part of it.
     *
     * @param Cart $cart
     * @return bool
     */
    public function overallApplicable(Cart $cart): bool
    {
        foreach ($cart->items() as $item) {
            if ($this->applicable($cart, $item)) {
                return true;
            }
        }

        return false;
    }

    public function applicable(Cart $cart, Discountable $eligibleForDiscount): bool
    {
        if (false === parent::applicable($cart, $eligibleForDiscount)) {
            return false;
        }

        if (! $eligibleForDiscount instanceof CartItem) {
            return false;
        }

        $sortedBySalePrice = $cart->items()->reject(function (CartItem $item) use ($cart) {
            return $item->isAddedAsFreeItemDiscount() || ! parent::applicable($cart, $item);
        })->sortBy(function (CartItem $item) {
            return $item->salePrice(true)->getAmount();
        });

        return $sortedBySalePrice->first()->id() === $eligibleForDiscount->id();
    }

    public function apply(Cart $cart)
    {
        foreach ($cart->items() as $item) {
            if ($this->applicable($cart, $item)) {
                $this->applyToCartItem($cart, $item);
            }
        }
    }

    private function applyToCartItem(Cart $cart, CartItem $cartItem)
    {
        $discountBasePrice = $cartItem->discountBasePriceAsMoney($this->conditions)->divide($cartItem->quantity());
        $discountAmount = $this->discountAmount($cart, $cartItem);

        if (isset($this->data['translations'])) {
            $discountDescriptions = array_map(function ($translation) {
                return $translation['description'];
            }, $this->data['translations']);
            $cartItem->addNote(CartNote::fromTranslations($discountDescriptions)->tag('cart', 'add_to_cart')->secondary());
        }

        $cartItem->addDiscount(new CartDiscount(
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
        $total = Cash::zero();

        foreach ($cart->items() as $item) {
            if ($this->applicable($cart, $item)) {
                $total = $total->add($this->discountAmount($cart, $item));
            }
        }

        return $total;
    }

    public function discountAmount(Cart $cart, Discountable $eligibleForDiscount): Money
    {
        // Cheapest discount is based on one item for one quantity
        $discountBasePrice = $eligibleForDiscount->discountBasePriceAsMoney($this->conditions)->divide($eligibleForDiscount->quantity());
        $discountBasePriceMinusDiscounts = $discountBasePrice->subtract($eligibleForDiscount->discountTotalAsMoney());

        $discountAmount = $discountBasePrice->multiply($this->percentage->asFloat());

        return $discountBasePriceMinusDiscounts->lessThanOrEqual($discountAmount)
            ? $discountBasePriceMinusDiscounts
            : $discountAmount;
    }
}