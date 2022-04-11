<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\ProductOptions;

use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;

class VariantForProductOption
{
    public readonly VariantId $variantId;
    private VariantOptions $options;

    private function __construct(VariantId $variantId, VariantOptions $options)
    {
        $this->variantId = $variantId;
        $this->options = $options;
    }

    public function hasExactOptionsMatch(VariantOptions $options): bool
    {
        return $this->options->equals($options);
    }

    public function hasOptionValueId(OptionValueId $optionValueId): bool
    {

    }

    public function getOptions(): VariantOptions
    {
        return $this->options;
    }

    public function getUrl(): string
    {

    }

    public static function fromMappedData(array $state, array $optionValueStates): static
    {
        return new static(
            VariantId::fromString($state['variant_id']),
            static::createOptions($optionValueStates)
        );
    }

    private static function createOptions(array $optionValueStates)
    {
        return VariantOptions::fromType(array_map(function($optionValueState){
            return ProductOption::fromMappedData($optionValueState);
        }, $optionValueStates));
    }
}
