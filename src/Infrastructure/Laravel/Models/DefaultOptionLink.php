<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Common\RendersData;
use Thinktomorrow\Trader\Application\Product\OptionLinks\OptionLink;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;

class DefaultOptionLink implements OptionLink
{
    use HasLocale;
    use RendersData;

    private Option $option;
    private OptionValue $optionValue;
    protected ?Variant $variant;
    protected bool $isActive = false;

    private function __construct(Option $option, OptionValue $optionValue, ?Variant $variant = null)
    {
        $this->option = $option;
        $this->optionValue = $optionValue;
        $this->variant = $variant;
    }

    public static function from(Option $option, OptionValue $optionValue, ?Variant $variant = null): static
    {
        return new static($option, $optionValue, $variant);
    }

    public function getOptionId(): string
    {
        return $this->optionValue->optionId->get();
    }

    public function getOptionLabel(): string
    {
        return $this->data('label', null, '', $this->option->getData());
    }

    public function getValueLabel(): string
    {
        return $this->data('value', null, '', $this->optionValue->getData());
    }

    public function getUrl(): ?string
    {
        if (! $this->variant) {
            return null;
        }

        return '/' . $this->variant->variantId->get();
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function markActive(): void
    {
        $this->isActive = true;
    }
}
