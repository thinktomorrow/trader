<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\ShippingProfile;

interface ShippingProfileForCart
{
    public static function fromMappedData(array $state): static;

    public function getShippingProfileId(): string;
    public function getProviderId(): string;
    public function getTitle(): string;
    public function getDescription(): ?string;
    public function requiresAddress(): bool;
    public function setImages(iterable $images): void;
    public function getImages(): iterable;
}
