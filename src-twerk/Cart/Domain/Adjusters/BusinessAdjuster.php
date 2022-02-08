<?php

namespace Thinktomorrow\Trader\Purchase\Cart\Adjusters;

use Optiphar\Invoices\Vat\VatNumberValidation;
use Thinktomorrow\Trader\Purchase\Cart\Cart;

class BusinessAdjuster implements Adjuster
{
    public function adjust(Cart $cart)
    {
        $cart->replaceData('details.is_business', $this->isBusiness($cart));

        $isTaxApplicable = $this->isTaxApplicable($cart);

        $cart->replaceData('details.is_tax_applicable', $isTaxApplicable);
        $cart->shipping()->replaceData('is_tax_applicable', $isTaxApplicable);
        $cart->payment()->replaceData('is_tax_applicable', $isTaxApplicable);

        foreach ($cart->items() as $item) {
            $item->replaceData('is_tax_applicable', $isTaxApplicable);
        }
    }

    private function isBusiness(Cart $cart): bool
    {
        return ($this->isCompany($cart) && $this->isVatValid($cart));
    }

    private function isCompany(Cart $cart): bool
    {
        return $cart->payment()->addressSalutation() == 'company';
    }

    private function isVatValid(Cart $cart): bool
    {
        if (! $cart->payment()->addressVat()) {
            return false;
        }

        return (new VatNumberValidation($cart->payment()->addressVat(), $cart->payment()->addressValidVat()))->isValid();
    }

    /**
     * Check if VAT should be applied
     *
     * 0) Client is considered a company if a valid vat is passed
     * 1) If client is a company and company is located in same country as our business, vat is applied
     * 2) If client is a company but is located in another country, vat is not applicable
     * 3) If client is a person, vat from the business country is applicable
     *
     * @param Cart $cart
     * @return bool
     */
    private function isTaxApplicable(Cart $cart): bool
    {
        if (! $cart->payment()->addressVat()) {
            return true;
        }

        return (new VatNumberValidation($cart->payment()->addressVat(), $cart->payment()->addressValidVat()))->isApplicable();
    }
}
