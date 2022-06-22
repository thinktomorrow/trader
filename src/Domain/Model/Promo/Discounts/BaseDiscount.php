<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Discounts;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Promo\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

abstract class BaseDiscount
{
    use HasData;

    public readonly PromoId $promoId;
    public readonly DiscountId $discountId;

    /** @var Condition[] */
    protected array $conditions;

    public function updateConditions(array $conditions): void
    {
        Assertion::allIsInstanceOf($conditions, Condition::class);

        $this->conditions = $conditions;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function getChildEntities(): array
    {
        return [
            Condition::class => array_map(fn (Condition $condition) => $condition->getMappedData(), $this->conditions),
        ];
    }

    public function getMappedData(): array
    {
        return [
            'promo_id' => $this->promoId->get(),
            'discount_id' => $this->discountId->get(),
            'key' => static::getMapKey(),
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        Assertion::allIsInstanceOf($childEntities[Condition::class], Condition::class);

        $object = new static();

        $object->promoId = PromoId::fromString($aggregateState['promo_id']);
        $object->discountId = DiscountId::fromString($state['discount_id']);
        $object->data = json_decode($state['data'], true);
        $object->conditions = $childEntities[Condition::class];

        return $object;
    }

    abstract public static function getMapKey(): string;
}
