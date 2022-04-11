<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\UpdateProduct;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;

class UpdateProductOptionItem
{
    private ?string $optionId;
    private array $values;

    /**
     * DTO object for updating product options.
     * Payload format looks like this:
     *
     * [
     *    'id' => '123', // null
     *    'values' => [
     *        [
     *            'id' => '123', // or null
     *            'data' => [
     *                'label' => ['nl' => 'label nl'],
     *                'custom' => 'foobar',
     *            ],
     *        ],
     *    ],
     * ]
     */

    public function __construct(?string $optionId, array $values)
    {
        Assertion::allIsInstanceOf($values, UpdateProductOptionValueItem::class);

        $this->optionId = $optionId;
        $this->values = $values;
    }

    public function getOptionId(): ?OptionId
    {
        return $this->optionId ? OptionId::fromString($this->optionId) : null;
    }

    /**
     * @return UpdateProductOptionValueItem[]
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
