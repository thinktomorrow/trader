<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\ProductOptions;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Common\ArrayCollection;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class VariantProductOptionsCollection extends ArrayCollection
{
    public static function fromType(array $items): static
    {
        Assertion::allIsInstanceOf($items, VariantProductOptions::class);

        return new static($items);
    }

    public function find(VariantId $variantId): VariantProductOptions
    {
        /** @var VariantProductOptions $item */
        foreach($this->items as $item) {
            if($item->variantId->equals($variantId)) {
                return $item;
            }
        }

        throw new \InvalidArgumentException(static::class . ' does not contain variant by variant id [' . $variantId->get() . '].');
    }

    public function findByOptionValues(VariantOptions $variantOptions): ?VariantProductOptions
    {
        /** @var VariantProductOptions $item */
        foreach($this->items as $item) {
            if($item->hasExactOptionsMatch($variantOptions)) {
                return $item;
            }
        }

        return null;
    }
}
