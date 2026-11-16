<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Discounts;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

abstract class BaseDiscount
{
    use HasData;

    public readonly PromoId $promoId;

    public readonly PromoDiscountId $discountId;

    /** @var list<Condition> */
    protected array $conditions;

    protected DiscountableType $discountableType;

    public function getPromoId(): PromoId
    {
        return $this->promoId;
    }

    public function getDiscountId(): PromoDiscountId
    {
        return $this->discountId;
    }

    public function getDiscountableType(): DiscountableType
    {
        return $this->discountableType;
    }

    public static function fixedDiscountableType(): ?DiscountableType
    {
        return null;
    }

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
            'discountable_type' => $this->discountableType->value,
            'data' => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState, array $childEntities = []): static
    {
        Assertion::allIsInstanceOf($childEntities[Condition::class], Condition::class);

        $object = new static;

        $object->promoId = PromoId::fromString($aggregateState['promo_id']);
        $object->discountId = PromoDiscountId::fromString($state['discount_id']);
        $object->data = json_decode($state['data'], true);
        $object->discountableType = static::resolveDiscountableType($state);
        $object->conditions = $childEntities[Condition::class];

        return $object;
    }

    protected static function resolveDiscountableType(array $state): DiscountableType
    {
        $fixedType = static::fixedDiscountableType();

        if (! isset($state['discountable_type']) && $fixedType === null) {
            throw new \InvalidArgumentException('Specify discountable_type for discount ['.static::getMapKey().']. Important for line, shipping and payment discounts.');
        }

        $type = isset($state['discountable_type'])
            ? DiscountableType::from($state['discountable_type'])
            : $fixedType;

        if ($fixedType !== null && $type !== $fixedType) {
            throw new \InvalidArgumentException('Invalid discountable_type for built-in discount ['.static::getMapKey().'].');
        }

        return $type;
    }

    abstract public static function getMapKey(): string;
}
