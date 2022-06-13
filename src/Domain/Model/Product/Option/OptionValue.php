<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Product\Option;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;

class OptionValue implements ChildEntity
{
    use HasData;

    public readonly OptionId $optionId;
    public readonly OptionValueId $optionValueId;

    private function __construct()
    {
    }

    public static function create(OptionId $optionId, OptionValueId $optionValueId, array $data): static
    {
        $optionValue = new static();

        $optionValue->optionId = $optionId;
        $optionValue->optionValueId = $optionValueId;
        $optionValue->data = $data;

        return $optionValue;
    }

    public function getMappedData(): array
    {
        return [
            'option_id' => $this->optionId->get(),
            'option_value_id' => $this->optionValueId->get(),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $optionValue = new static();

        $optionValue->optionId = OptionId::fromString($state['option_id']);
        $optionValue->optionValueId = OptionValueId::fromString($state['option_value_id']);
        $optionValue->data = json_decode($state['data'], true);

        return $optionValue;
    }
}
