<?php

namespace Thinktomorrow\Trader\Sales\Domain\Types;

use Thinktomorrow\Trader\Sales\Domain\Sale;

final class TypeKey
{
    private static $mapping = [
        'percentage_off'      => PercentageOffSale::class,
        'fixed_amount_off'    => FixedAmountOffSale::class,
        'fixed_amount'        => FixedAmountSale::class,
        'fixed_custom_amount' => FixedCustomAmountSale::class, // Saleprice as set per item
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
        if (!isset(self::$mapping[$type])) {
            throw new \InvalidArgumentException('Invalid type [' . $type . ']. Not found as available sale class.');
        }

        return new self($type);
    }

    public static function fromSale(Sale $sale)
    {
        if (false === ($key = array_search(get_class($sale), self::$mapping))) {
            throw new \InvalidArgumentException('Sale [' . get_class($sale) . '] not found as available sale type.');
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
