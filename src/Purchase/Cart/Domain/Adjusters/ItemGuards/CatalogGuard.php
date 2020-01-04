<?php

namespace Thinktomorrow\Trader\Purchase\Cart\Adjusters\ItemGuards;

use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\CartItem;
use Thinktomorrow\Trader\Purchase\Cart\Adjusters\Adjuster;
use Thinktomorrow\Trader\Purchase\Cart\CartNote;
use Optiphar\Catalogs\CatalogExclusiveness;

class CatalogGuard implements Adjuster
{
    public function adjust(Cart $cart)
    {
        foreach($cart->items() as $item){
            if($this->isExcluded($cart, $item)) {

                $cart->addNote(CartNote::fromTransKey('basket.notadded.excluded', [
                    'name' => $item->label(),
                    'country' => $cart->shipping()->addressCountry(),
                ])->tag('add_to_cart','cart'));

                $cart->items()->remove($item->id());
            }
            // If not excluded, still check if if could be excluded but we're not sure yet because we don't know shipping country yet.
            elseif( ! $cart->shipping()->addressCountryId() && $this->isExcludedForAny($cart, $item)){
                $item->addNote(CartNote::fromTransKey('basket.item_possibly_not_available.excluded', [
                    'name' => $item->label(),
                    'exclusive' => 'BE',
                ])->subtle()->tag('add_to_cart','cart'));
            }
        }
    }

    public function isExcluded(Cart $cart, CartItem $cartItem): bool
    {
        if( ! $cart->shipping()->addressCountryId()) return false;

        if( ! CatalogExclusiveness::isAllowedForCatalog($cartItem->productId(), $cart->shipping()->addressCountryId())){
            return true;
        }

        return false;
    }

    public function isExcludedForAny(Cart $cart, CartItem $cartItem): bool
    {
        return CatalogExclusiveness::isExclusiveForAnyCatalog($cartItem->productId());
    }

}
