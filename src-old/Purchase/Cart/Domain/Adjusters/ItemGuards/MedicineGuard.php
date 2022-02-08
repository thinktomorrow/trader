<?php

namespace Purchase\Cart\Domain\Adjusters\ItemGuards;

use Thinktomorrow\Trader\Purchase\Cart\Cart;
use Thinktomorrow\Trader\Purchase\Cart\CartItem;
use Thinktomorrow\Trader\Purchase\Cart\CartNote;
use Thinktomorrow\Trader\Purchase\Cart\Adjusters\Adjuster;

class MedicineGuard implements Adjuster
{
    public function adjust(Cart $cart)
    {
        foreach($cart->items() as $item){
            if($this->isRestricted($cart, $item)) {
                $cart->addNote(CartNote::fromTransKey('basket.notadded.medicin', [
                    'name'      => $item->label(),
                    'country'   => $cart->shipping()->addressCountry(),
                ])->tag('add_to_cart')->red());

                $cart->items()->remove($item->id());
            } elseif($this->restrictedMedicine($item)){
                $item->addNote(CartNote::fromTransKey('basket.added.medicin_not_allowed')->tag('add_to_cart')->red());
                $item->addNote(CartNote::fromTransKey('promos.disallowed_promo.label')->tag('cart')->subtle());
            }
        }
    }

    public function isRestricted(Cart $cart, CartItem $cartItem): bool
    {
        if( ! $cart->shipping()->addressCountryId()) return false;

        // Medicine can be bought only in BE or NL
        if(!in_array($cart->shipping()->addressCountryId(),['BE', 'NL'])) {

            return $this->restrictedMedicine($cartItem);
        }

        return false;
    }

    private function restrictedMedicine(CartItem $cartItem): bool
    {
        // Exception is brand Labolife (id: 474) which is a medicine but is purchasable outside of BE,NL
        if(in_array($cartItem->brandId(), [474])) return false;

        return $cartItem->isMedicine();
    }
}
