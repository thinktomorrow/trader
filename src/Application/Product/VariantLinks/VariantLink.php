<?php

namespace Thinktomorrow\Trader\Application\Product\VariantLinks;

use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;

interface VariantLink
{
    public static function fromOption(Option $option, OptionValue $optionValue, ?Variant $variant = null): static;

    public static function fromVariant(Variant $variant): static;

    public function getGroupId(): string;

    public function getGroupLabel(): string;

    public function getLabel(): string;

    public function getUrl(): ?string;

    public function isActive(): bool;

    public function markActive(): void;
}
