<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Option;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;

class Option implements ChildEntity
{
    public readonly ProductId $productId;
    public readonly OptionId $optionId;
    private array $optionValueIds = [];

    private function __construct()
    {

    }

    public static function create(ProductId $productId, OptionId $optionId): static
    {
        $option = new static();

        $option->productId = $productId;
        $option->optionId = $optionId;

        return $option;
    }

    public function getMappedData(): array
    {
        return [
            'product_id' => $this->productId->get(),
            'option_id' => $this->optionId->get(),
            'option_value_ids' => array_map(fn(OptionValueId $optionValueId) => $optionValueId->get() , $this->optionValueIds),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $option = new static();

        $option->productId = $aggregateState['product_id'];
        $option->optionId = $state['option_id'];
        $option->optionValueIds = array_map(fn($optionValueId) => OptionValueId::fromString($optionValueId), $state['option_value_ids']);

        return $option;
    }

    public function updateOptionValueIds(array $optionValueIds): void
    {
        Assertion::allIsInstanceOf($optionValueIds, OptionValueId::class);

        $this->optionValueIds = $optionValueIds;
    }
}
