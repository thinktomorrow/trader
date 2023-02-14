<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\PaymentMethod;

interface PaymentMethodForCart
{
    public static function fromMappedData(array $state): static;

    public function getPaymentMethodId(): string;
    public function getProviderId(): string;
    public function getTitle(): string;
    public function getDescription(): ?string;
    public function setImages(iterable $images): void;
    public function getImages(): iterable;
}
