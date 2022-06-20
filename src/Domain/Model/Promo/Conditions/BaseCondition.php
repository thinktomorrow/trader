<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Conditions;

use Thinktomorrow\Trader\Domain\Common\Entity\HasData;

abstract class BaseCondition
{
    use HasData;

    public function getMappedData(): array
    {
        return [
            'key' => static::getMapKey(),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $object = new static();

        $object->data = json_decode($state['data'], true);

        return $object;
    }

    abstract public static function getMapKey(): string;
}
