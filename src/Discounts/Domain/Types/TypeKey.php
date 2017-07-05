<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Thinktomorrow\Trader\Discounts\Domain\Discount;

final class TypeKey
{
    private static $mapping = [
        'percentage_off' => PercentageOffDiscount::class,
        'percentage_off_item' => PercentageOffItemDiscount::class,
        'free_item' => FreeItemDiscount::class,
    ];

    /**
     * @var string
     */
    private $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function fromString(string $type)
    {
        if(!isset(self::$mapping[$type]))
        {
            throw new \InvalidArgumentException('Invalid type ['.$type.']. Not found as available discount class.');
        }

        return new self($type);
    }

    public static function fromDiscount(Discount $discount)
    {
        if(false === ($key = array_search(get_class($discount),self::$mapping)))
        {
            throw new \InvalidArgumentException('Discount ['.get_class($discount).'] not found as available discount class.');
        }

        return new self($key);
    }

    public function get(): string
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return $this->type;
    }

    public function class(): string
    {
        return self::$mapping[$this->type];
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && (string)$this === (string)$other;
    }
}