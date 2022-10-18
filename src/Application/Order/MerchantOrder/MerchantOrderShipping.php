<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\MerchantOrder;

interface MerchantOrderShipping
{
    public static function fromMappedData(array $state, array $cartState, iterable $discounts): static;

    public function getShippingId(): string;
    public function getShippingProfileId(): ?string;
    public function getShippingState(): string;
    public function getCostPrice(): string;
    public function includeTax(bool $includeTax = true): void;

    /** @return MerchantOrderDiscount[] */
    public function getDiscounts(): iterable;

    public function requiresAddress(): bool;
    public function getTitle(): ?string;
    public function getDescription(): ?string;
}
