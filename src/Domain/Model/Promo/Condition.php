<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;

final class Condition implements ChildEntity
{
    use HasData;

    public readonly string $mappingKey;

    public function getMappedData(): array
    {
        return [
            'key' => $this->mappingKey,
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $object = new static();

        $object->mappingKey = $state['key'];
        $object->data = json_decode($state['data'], true);

        return $object;
    }
}
