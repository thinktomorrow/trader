<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

// TODO: get all available types to validate them here
// maybe a availableDiscountTypes array from config or something
class DiscountType
{
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
        return new self($type);
    }

    public function get(): string
    {
        return $this->type;
    }
}