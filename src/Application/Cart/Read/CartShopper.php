<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Read;

use Thinktomorrow\Trader\Domain\Model\VatNumber\HasVatNumber;

interface CartShopper extends HasVatNumber
{
    public static function fromMappedData(array $state, array $cartState): static;

    public function getShopperId(): string;

    public function getEmail(): string;

    /** Shopper refers to a customer account */
    public function isCustomer(): bool;

    /** Shopper has no customer account and is a one time shopper */
    public function isGuest(): bool;

    /** Does the shopper have a business profile */
    public function isBusiness(): bool;
}
