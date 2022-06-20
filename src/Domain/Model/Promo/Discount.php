<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildAggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;

final class Discount implements ChildAggregate
{
    use HasData;

    public readonly PromoId $promoId;
    public readonly string $mappingKey;

    /** @var Condition[] */
    private array $conditions;

    public function updateConditions(array $conditions): void
    {
        Assertion::allIsInstanceOf($conditions, Condition::class);

        $this->conditions = $conditions;
    }

    public function getMappedData(): array
    {
        return [
            'promo_id' => $this->promoId->get(),
            'key' => $this->mappingKey,
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [
            Condition::class => array_map(fn (Condition $condition) => $condition->getMappedData(), $this->conditions),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        $object = new static();

        $object->promoId = PromoId::fromString($aggregateState['promo_id']);
        $object->mappingKey = $state['key'];

        if (array_key_exists(Condition::class, $childEntities)) {
            $object->conditions = array_map(fn ($conditionState) => Condition::fromMappedData($conditionState, $state), $childEntities[Condition::class]);
        }
        $object->data = json_decode($state['data'], true);

        return $object;
    }
}
