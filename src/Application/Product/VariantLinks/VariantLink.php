<?php

namespace Thinktomorrow\Trader\Application\Product\VariantLinks;

use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\VariantProperty;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty as VariantVariantProperty;

interface VariantLink
{
    public static function fromVariantProperty(VariantProperty|VariantVariantProperty $property, ?Variant $variant = null): static;

    public static function fromVariant(Variant $variant): static;

    public function getGroupId(): string;

    public function getGroupLabel(): string;

    public function getLabel(): string;

    public function getUrl(): ?string;

    public function isActive(): bool;

    public function markActive(): void;
}
