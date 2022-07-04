<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\Read;

interface CartShippingAddress
{
    public static function fromMappedData(array $state, array $cartState): static;

    public function getCountryId(): ?string;
    public function getPostalCode(): ?string;
    public function getCity(): ?string;
    public function getLine1(): ?string;
    public function getLine2(): ?string;

    public function getTitle(): ?string;
    public function getDescription(): ?string;
}
