<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\ProductOptions;

use Assert\Assertion;
use Thinktomorrow\Trader\Application\Common\ArrayCollection;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValueId;

class ProductOptions extends ArrayCollection
{
    public static function fromType(array $items): static
    {
        Assertion::allIsInstanceOf($items, ProductOption::class);

        return new static($items);
    }

    public function getGrouped(): array
    {
        $grouped = [];

        /** @var ProductOption $item */
        foreach($this->items as $item) {
            $optionId = $item->optionId->get();

            if(!isset($grouped[$optionId])) $grouped[$optionId] = [];

            $grouped[$optionId][] = $item;
        }

        return $grouped;
    }

    public function hasOptionValue(OptionValueId $optionValueId): bool
    {

    }

    public function getOptions()
    {
        // Grouped by option

        // ProductOption ...
        return [
            'color' => [
                'label' => 'color',
                'value' => 'blauw',
            ],
        ];

        // OptionLinks

        return [
            [
                'label' => 'color',
                'options' => [
                    'url' => '/products/112',
                    'is_active' => false,
                    'value' => 'blauw',
                ],
            ],
        ];

        // All options grouped per type
        // With per type:
        // label (localized)
        // With per option:
        // url for this option (can be null)
        // isActive boolean
        // value (localized)
    }
}
