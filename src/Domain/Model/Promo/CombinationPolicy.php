<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo;

use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;

final readonly class CombinationPolicy
{
    /** @param list<DiscountableType> $combinesWith */
    public function __construct(public array $combinesWith, private bool $multipleProductDiscountsPerLine = false)
    {
        foreach ($combinesWith as $discountableType) {
            if (! $discountableType instanceof DiscountableType) {
                throw new \InvalidArgumentException('Combination types must be DiscountableType values.');
            }
        }
    }

    public function allows(DiscountableType $discountableType): bool
    {
        return in_array($discountableType, $this->combinesWith, true);
    }

    public function allowsProductStackingWith(self $other): bool
    {
        return $this->multipleProductDiscountsPerLine && $other->multipleProductDiscountsPerLine;
    }

    public function allowsMultipleProductDiscountsPerLine(): bool
    {
        return $this->multipleProductDiscountsPerLine;
    }

    /** @return list<string> */
    public function toArray(): array
    {
        return array_map(fn (DiscountableType $type): string => $type->value, $this->combinesWith);
    }

    /** @param list<string> $types */
    public static function fromArray(array $types, bool $multipleProductDiscountsPerLine = false): self
    {
        return new self(array_map(fn (string $type): DiscountableType => DiscountableType::from($type), $types), $multipleProductDiscountsPerLine);
    }
}
