<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

interface MerchantOrderBillingAddress
{
    public static function fromMappedData(array $state, array $cartState): static;

    public function getCountry(): ?string;
    public function getPostalCode(): ?string;
    public function getCity(): ?string;
    public function getLine1(): ?string;
    public function getLine2(): ?string;
}
