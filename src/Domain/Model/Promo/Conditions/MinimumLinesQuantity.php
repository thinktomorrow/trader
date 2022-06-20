<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Conditions;

use Thinktomorrow\Trader\Domain\Model\Promo\Condition;

class MinimumLinesQuantity extends BaseCondition implements Condition
{
    private int $minimum_quantity;

    public static function getMapKey(): string
    {
        return 'minimum_lines_quantity';
    }

    public function getMappedData(): array
    {
        return array_merge(parent::getMappedData(), [
            'data' => json_encode(array_merge($this->data, ['minimum_quantity' => $this->minimum_quantity])),
        ]);
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $condition = parent::fromMappedData($state, $aggregateState);

        $data = json_decode($state['data'], true);
        $condition->minimum_quantity = (int) $data['minimum_quantity'];

        return $condition;
    }
}
