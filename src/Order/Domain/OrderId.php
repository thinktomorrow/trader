<?php

namespace Thinktomorrow\Trader\Order\Domain;

class OrderId
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

    public function __toString()
    {
        return (string) $this->get();
    }
}