<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\UpdateProduct;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;

class UpdateProductOptionItem
{
    private ?string $optionId;
    private array $data;

    /** @var UpdateProductOptionValueItem[] */
    private array $values;

    /**
     * DTO object for updating product options.
     * Payload format looks like this:
     *
     * [
     *    'option_id' => '123', // null
     *    'data' => ['label' => 'nl' => ['kleur']],
     *    'values' => [
     *        [
     *            'option_value_id' => '123', // or null
     *            'data' => [
     *                'label' => ['nl' => 'groen'],
     *                'custom' => 'foobar',
     *            ],
     *        ],
     *    ],
     * ]
     */

    public function __construct(?string $optionId, array $data, array $values)
    {
        Assertion::allIsInstanceOf($values, UpdateProductOptionValueItem::class);

        $this->optionId = $optionId;
        $this->data = $data;
        $this->values = $values;
    }

    public function getOptionId(): ?OptionId
    {
        return $this->optionId ? OptionId::fromString($this->optionId) : null;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getValues(): array
    {
        return $this->values;
    }
}
