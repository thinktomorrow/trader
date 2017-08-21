<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

final class ConditionKey
{
    private static $mapping = [
        'minimum_amount'        => MinimumAmount::class,
        'purchasable_ids'       => ItemWhitelist::class,
        'minimum_item_quantity' => MinimumItemQuantity::class,
        'start_at'              => Period::class,
        'end_at'                => Period::class, // TODO avoid duplicate conditional loading!!
    ];

    /**
     * @var string
     */
    private $condition;

    public function __construct(string $condition)
    {
        $this->condition = $condition;
    }

    public static function fromString(string $condition)
    {
        if (!isset(self::$mapping[$condition])) {
            throw new \InvalidArgumentException('Invalid parameter ['.$condition.']. Not found as available Condition class.');
        }

        return new self($condition);
    }

    public static function fromCondition(Condition $discount)
    {
        if (false === ($key = array_search(get_class($discount), self::$mapping))) {
            throw new \InvalidArgumentException('Condition ['.get_class($discount).'] not found as available Condition class.');
        }

        return new self($key);
    }

    public function get(): string
    {
        return $this->condition;
    }

    public function __toString(): string
    {
        return $this->condition;
    }

    public function class(): string
    {
        return self::$mapping[$this->condition];
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && (string) $this === (string) $other;
    }
}
