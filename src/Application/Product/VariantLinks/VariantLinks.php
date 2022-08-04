<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\VariantLinks;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Common\ArrayCollection;

class VariantLinks extends ArrayCollection
{
    public static function fromType(array $items): static
    {
        Assertion::allIsInstanceOf($items, VariantLink::class);

        return new static($items);
    }

    public function add(VariantLink $optionLink): static
    {
        return new static(array_merge($this->items, [$optionLink]));
    }

    public function getGrouped(): array
    {
        $grouped = [];

        /** @var VariantLink $item */
        foreach ($this->items as $item) {
            if (! isset($grouped[$item->getGroupId()])) {
                $grouped[$item->getGroupId()] = [];
            }
            $grouped[$item->getGroupId()][] = $item;
        }

        return $grouped;
    }
}
