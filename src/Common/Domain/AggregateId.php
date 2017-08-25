<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Domain;

use Assert\Assertion;

trait AggregateId
{
    /**
     * @var string|int
     */
    private $id;

    private function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @param int $id
     * @return static
     */
    public static function fromInteger(int $id)
    {
        Assertion::notEmpty($id);

        return new static($id);
    }

    /**
     * @param string $id
     * @return static
     */
    public static function fromString(string $id)
    {
        Assertion::notEmpty($id);

        return new static($id);
    }

    public function get()
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function equals($otherAggregateId): bool
    {
        return get_class($otherAggregateId) === get_class($this)
            && (string) $this->get() === (string) $otherAggregateId->get();
    }
}
