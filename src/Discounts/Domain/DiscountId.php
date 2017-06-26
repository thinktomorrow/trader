<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

class DiscountId
{
    /**
     * @var int
     */
    private $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public static function fromInteger(int $id)
    {
        return new self($id);
    }

    public function get(): int
    {
        return $this->id;
    }
}