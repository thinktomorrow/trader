<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Customer\Read;

use Thinktomorrow\Trader\Domain\Common\Locale;

interface CustomerRead
{
    public static function fromMappedData(array $state, array $childObjects): static;

    public function getCustomerId(): string;

    public function getEmail(): string;

    public function getCustomerLocale(): Locale;

    /** Does the shopper have a business profile */
    public function isBusiness(): bool;

    public function getShippingAddress(): ?CustomerShippingAddress;
    public function getBillingAddress(): ?CustomerBillingAddress;
}
