<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Cart\Ports;

use Money\Money;
use Optiphar\Cart\Cart;
use Optiphar\Cart\CartItem;
use Optiphar\Cart\CartItems;
use Optiphar\Cart\CartNotes;
use Optiphar\Cart\CartPayment;
use Optiphar\Cart\CartCustomer;
use Optiphar\Cart\CartDiscount;
use Optiphar\Cart\CartShipping;
use Optiphar\Cart\CartReference;
use Optiphar\Cashier\Percentage;
use Optiphar\Discounts\DiscountId;
use Optiphar\Discounts\Types\TypeKey;
use Optiphar\Cart\Adjusters\BusinessAdjuster;
use Optiphar\Cart\Adjusters\CustomerAdjuster;
use Optiphar\Cart\Adjusters\DiscountAdjuster;
use Optiphar\Cart\Adjusters\PaymentCostAdjuster;
use Optiphar\Cart\Adjusters\DutchTaxRateAdjuster;
use Optiphar\Cart\Adjusters\ShippingCostAdjuster;
use Optiphar\Cart\Adjusters\PaymentMethodAdjuster;
use Optiphar\Cart\Adjusters\ShippingMethodAdjuster;
use Optiphar\Cart\Adjusters\ItemGuards\CatalogGuard;
use Optiphar\Cart\Adjusters\ItemGuards\MedicineGuard;
use Optiphar\Cart\Adjusters\ItemPriceDescriptionAdjuster;
use Optiphar\Cart\Adjusters\DefaultShippingMethodAdjuster;
use Optiphar\Cart\Adjusters\DefaultShippingCountryAdjuster;
use Optiphar\Cart\Adjusters\ItemGuards\AddedAsFreeItemGuard;
use Optiphar\Cart\Adjusters\ItemGuards\StockAndAvailabilityGuard;

class CartFactory
{
    /**
     * Strategy of different actions on the cart in order to shape it to the current context. The
     * sequence is important as it is handled first to last and each adjuster can influence
     * the cart state as it is passed on to the next adjuster.
     *
     * @var array
     */
    private $adjusters = [
        // Item guards
        AddedAsFreeItemGuard::class,
        StockAndAvailabilityGuard::class,
        BusinessAdjuster::class,

        // Apply the cart discounts.
        DiscountAdjuster::class,

        // General cart adjusters
        CustomerAdjuster::class,
        DefaultShippingCountryAdjuster::class,
        DefaultShippingMethodAdjuster::class,
        ShippingMethodAdjuster::class,
        PaymentMethodAdjuster::class,
        ShippingCostAdjuster::class,
        PaymentCostAdjuster::class,

        ItemPriceDescriptionAdjuster::class,
    ];

    /** @var CartItemsFactory */
    private $cartItemsFactory;

//    public function __construct(CartItemsFactory $cartItemsFactory)
//    {
//        $this->cartItemsFactory = $cartItemsFactory;
//    }

    public function create(CartReference $cartReference, array $data): Cart
    {
        return Cart::fromData($cartReference, $this->staleItems($data), $this->staleData($data));
    }

    public function createFresh(CartReference $cartReference, array $data): Cart
    {
        $cart = Cart::fromData($cartReference, $this->freshItems($data), $this->freshData($data));

        foreach($this->adjusters as $adjuster) {
            app($adjuster)->adjust($cart);
        }

        return $cart;
    }

    public function createEmpty(CartReference $cartReference): Cart
    {
        return Cart::fromData($cartReference,  new CartItems());
    }

    private function freshItems(array $data): CartItems
    {
        return $this->cartItemsFactory->create($data['items']);
    }

    private function freshData(array $data): array
    {
        // Items are already fetched as separate parameter
        unset($data['items']);

        return array_merge($data, [
            'shipping'  => new CartShipping($data['shipping']['method'], Money::EUR($data['shipping']['subtotal']), Percentage::fromPercent($data['shipping']['taxrate']), $data['shipping']),
            'payment'   => new CartPayment($data['payment']['method'], Money::EUR($data['payment']['subtotal']), Percentage::fromPercent($data['payment']['taxrate']), $data['payment']),
            'customer'  => new CartCustomer($data['customer']['customerid'], $data['customer']['email'], $data['customer']),
            'discounts' => collect(),
            'notes'     => new CartNotes(),
        ]);
    }

    private function staleData(array $data): array
    {
        $discounts = collect($data['discounts'])->map(function(array $rawDiscount){
            return new CartDiscount(
                DiscountId::fromString($rawDiscount['discountid']),
                TypeKey::fromString($rawDiscount['typekey']),
                Money::EUR($rawDiscount['total']),
                Percentage::fromPercent($rawDiscount['taxrate']),
                Money::EUR($rawDiscount['basetotal']),
                Percentage::fromPercent($rawDiscount['percentage']),
                $rawDiscount['data']
            );
        });

        return array_merge($data, [
            'items'     => $this->staleItems($data),
            'discounts' => $discounts,
            'shipping'  => new CartShipping($data['shipping']['method'], Money::EUR($data['shipping']['subtotal']), Percentage::fromPercent($data['shipping']['taxrate']), $data['shipping']),
            'payment'   => new CartPayment($data['payment']['method'], Money::EUR($data['payment']['subtotal']), Percentage::fromPercent($data['payment']['taxrate']), $data['payment']),
            'customer'  => new CartCustomer($data['customer']['customerid'], $data['customer']['email'], $data['customer']),
        ]);
    }

    private function staleItems(array $data): CartItems
    {
        $items = collect($data['items'])->map(function(array $rawItem){

            $quantity = $rawItem['quantity'];
            unset($rawItem['quantity']);

            $rawDiscounts = $rawItem['discounts'];
            unset($rawItem['discounts']);

            $item = new CartItem(array_merge($rawItem, [
                'saleprice' => Money::EUR($rawItem['saleprice']),
                'price'     => Money::EUR($rawItem['price']),
                'taxrate'   => Percentage::fromPercent($rawItem['taxrate']),
            ]), $quantity);

            foreach($rawDiscounts as $rawDiscount){
                $item->addDiscount(new CartDiscount(
                    DiscountId::fromString($rawDiscount['discountid']),
                    TypeKey::fromString($rawDiscount['typekey']),
                    Money::EUR($rawDiscount['total']),
                    Percentage::fromPercent($rawDiscount['taxrate']),
                    Money::EUR($rawDiscount['basetotal']),
                    Percentage::fromPercent($rawDiscount['percentage']),
                    $rawDiscount['data']
                ));
            }

            return $item;
        });

        return new CartItems($items->all());
    }


}
