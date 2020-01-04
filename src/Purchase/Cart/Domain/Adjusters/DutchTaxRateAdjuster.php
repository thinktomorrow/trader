<?php

namespace Optiphar\Cart\Adjusters;

use Optiphar\Cart\Cart;
use Optiphar\Cashier\Cash;
use Optiphar\Cart\CartItem;
use Optiphar\Cashier\Percentage;

class DutchTaxRateAdjuster implements Adjuster
{
    private $cart;

    public function adjust(Cart $cart)
    {
        $this->cart = $cart;
        foreach($cart->items() as $item){
            // check if we need to look at the 6-9 taxrate rule
            if(! $this->productIsValidForTaxRateChange($item)) continue;

            //get the correct percentage and set it.
            $item->replaceData('taxrate',  Percentage::fromPercent($this->getTaxPercentage($item)));

            // Disable reversed tax adjustment by client request: The gross price is kept as is, even with the increased tax rate.
//            $item->replaceData('saleprice',  $this->adjustByTaxRate($item, $item->product()->salePriceAsMoney()));
//            $item->replaceData('price',  $this->adjustByTaxRate($item, $item->product()->priceAsMoney()));
        }
    }

    private function getTaxPercentage(CartItem $cartItem)
    {
        $tax_percentage = 6;
        $countryId = $this->cart->shipping()->addressCountryId();

        /**
         * Business rule: For orders with a NL billing address, all products with a Belgian
         * tax rate of 6 percent should be converted to the Dutch 9 percent. In Holland
         * the 6 to 9 conversion is in effect since january 2019
         */
        if($cartItem->taxRate()->asPercent() == '6' && $countryId == 'NL'){
            $tax_percentage = 9;
        }

        return $tax_percentage;
    }

    // this rule only applies to products with a taxrate of 6%
    private function productIsValidForTaxRateChange(CartItem $cartItem): bool
    {
        return $cartItem->product()->taxRateAsPercentage()->equals(Percentage::fromPercent(6));
    }

    private function adjustByTaxRate($item, $priceAsMoney)
    {
        $money = Cash::from($priceAsMoney)->subtractTaxPercentage($item->product()->taxRateAsPercentage());
        return Cash::from($money)->addPercentage($item->taxRate());
    }
}
