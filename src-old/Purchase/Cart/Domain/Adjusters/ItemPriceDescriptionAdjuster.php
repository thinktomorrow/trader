<?php

namespace Purchase\Cart\Domain\Adjusters;

use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\CartDiscount;
use Thinktomorrow\Trader\Purchase\Cart\Adjusters\Adjuster;
use function Thinktomorrow\Trader\Purchase\Cart\Adjusters\trans;

class ItemPriceDescriptionAdjuster implements Adjuster
{
    public function adjust(Cart $cart)
    {
        foreach($cart->items() as $item){

            // Base sale percentage - TODO: account for multiple sales
            $salePercentage = (int) $item->product()->sales()->sum(function($sale){ return $sale->percentage; });

            $item->setPriceDescription('-');

            // Default price description is based on saleprice
            if($item->product()->onPromotedSale()) {
                $item->setPriceDescription($salePercentage . '%');
            }

            if($item->discounts()->isEmpty()) continue;

            $shouldDiscountDiscounts = false;
            $totalDiscountPercentage = $salePercentage;
            $descriptions = [$salePercentage.'%'];

            /** @var CartDiscount $cartDiscount */
            foreach($item->discounts() as $cartDiscount) {

                $totalDiscountPercentage += $cartDiscount->percentageAsPercent();

                if($cartDiscount->type()->isType('cheapest_product_percentage')) {
                    $descriptions[] = trans('basket.table.discount-cheapest-promo', ['discount' => $salePercentage + $cartDiscount->percentageAsPercent() .'%']);
                    $shouldDiscountDiscounts = true;
                }
                elseif($cartDiscount->type()->isType('product_percentage')) {
                    $descriptions[] = $cartDiscount->percentageAsPercent().'%';
                    $shouldDiscountDiscounts = true;
                }


            }

            if($shouldDiscountDiscounts){
                $item->setPriceDescription($totalDiscountPercentage .'%<br><span class="tag tag-primary text-xs">' . implode($descriptions, ' + ').'</span>');
            }
        }
    }
}
