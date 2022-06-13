<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\OptionLinks;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Common\ArrayCollection;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultOptionLink;

class OptionLinks extends ArrayCollection
{
    public static function fromType(array $items): static
    {
        Assertion::allIsInstanceOf($items, DefaultOptionLink::class);

        return new static($items);
    }

    public function add(OptionLink $optionLink): static
    {
        return new static(array_merge($this->items, [$optionLink]));
    }

    public function getGrouped(): array
    {
        $grouped = [];

        /** @var OptionLink $item */
        foreach ($this->items as $item) {
            if (! isset($grouped[$item->getOptionId()])) {
                $grouped[$item->getOptionId()] = [];
            }
            $grouped[$item->getOptionId()][] = $item;
        }

        return $grouped;
    }
}
