<?php

namespace Purchase\Discounts\Domain\Types;

use Money\Money;
use Optiphar\Cashier\TaxRate;
use Optiphar\Cashier\Percentage;
use Optiphar\Discounts\Discount;
use Optiphar\Discounts\DiscountId;
use Optiphar\Discounts\Discountable;
use Optiphar\Promos\Common\Domain\Promo;
use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\CartNote;
use Thinktomorrow\Trader\Purchase\Cart\CartDiscount;
use Purchase\Discounts\Domain\Exceptions\CannotApplyDiscount;
use Thinktomorrow\Trader\Purchase\Cart\Ports\CartItemsFactory;
use function Optiphar\Discounts\Types\app;

class FreeProductsDiscount extends BaseDiscount implements Discount
{
    /** @var array */
    private $productIds;

    public function __construct(DiscountId $id, array $productIds, array $conditions, array $data = [])
    {
        parent::__construct($id, $conditions, $data);

        $this->productIds = $productIds;
    }

    public static function fromPromo(Promo $promo, array $conditions, array $data): Discount
    {
        return new static(
            DiscountId::fromString($promo->getId()->get()),
            [$promo->getDiscount()->get()],
            $conditions,
            $data
        );
    }

    public function apply(Cart $cart)
    {
        if (!$this->applicable($cart, $cart)) {
            throw new CannotApplyDiscount('Discount cannot be applied. One or more conditions have failed.');
        }

        foreach($this->productIds as $productId)
        {
            $freeItem = app(CartItemsFactory::class)->createItemById('free-' . $productId, $productId, 0);

            if(isset($this->data['translations']))
            {
                $discountDescriptions = array_map(function($translation){ return $translation['description']; }, $this->data['translations']);
                $freeItem->addNote(CartNote::fromTranslations($discountDescriptions)->tag('cart', 'add_to_cart')->secondary());
            }

            $freeItem->addDiscount(new CartDiscount(
                $this->id,
                TypeKey::fromDiscount($this),
                $freeItem->salePrice(true),
                TaxRate::default(),
                $freeItem->salePrice(true),
                Percentage::fromPercent(100),
                $this->data
            ));

            $cart->items()->add($freeItem);
        }
    }

    public function discountAmountTotal(Cart $cart): Money
    {
        return $this->discountAmount($cart, $cart);
    }

    public function discountAmount(Cart $cart, Discountable $eligibleForDiscount): Money
    {
        return Money::EUR(0);
    }
}
