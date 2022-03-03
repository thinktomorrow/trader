<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\ProductOptions;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Common\ArrayCollection;

class VariantOptions extends ArrayCollection
{
    public static function fromType(array $items): static
    {
        Assertion::allIsInstanceOf($items, ProductOption::class);

        static::assertOneValuePerOption($items);

        return new static($items);
    }

    /**
     * Merges a new option value into this set of variant options. Specifically for variant options it is
     * important that each option is only represented once. So this new option value will replace
     * an option value that shares the same option.
     */
    public function merge(ProductOption $option): static
    {
        $items = [];

        /** @var ProductOption $item */
        foreach($this->items as $i => $item) {
            $items[$i] = ($option->optionId->equals($item->optionId))
                ? $option
                : $item;
        }

        return static::fromType($items);
    }

//    public function getOptionValueIds(): array
//    {
//        return array_map(fn($item) => $item->optionValueId->get(), $this->items);
//    }

    private static function assertOneValuePerOption(array $items)
    {
        $optionIds = [];

        /** @var ProductOption $item */
        foreach($items as $item) {
            if(in_array($item->optionId->get(), $optionIds)) {
                throw new \RuntimeException('Only one option value per option allowed for variants');
            }

            $optionIds[] = $item->optionId->get();
        }
    }
}
