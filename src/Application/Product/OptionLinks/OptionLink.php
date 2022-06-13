<?php

namespace Thinktomorrow\Trader\Application\Product\OptionLinks;

use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;

interface OptionLink
{
    public static function from(Option $option, OptionValue $optionValue, ?Variant $variant = null): static;

    public function getOptionId(): string;

    public function getOptionLabel(): string;

    public function getValueLabel(): string;

    public function getUrl(): ?string;

    public function isActive(): bool;

    public function markActive(): void;
}
