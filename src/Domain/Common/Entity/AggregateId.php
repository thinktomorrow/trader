<?php

namespace Thinktomorrow\Trader\Domain\Common\Entity;

trait AggregateId
{
    private string $id;

    private function __construct()
    {
        //
    }

    /**
     * @param string $id
     * @return static
     */
    public static function fromString(string $id): static
    {
        $aggregateId = new static();
        $aggregateId->id = $id;

        return $aggregateId;
    }

    public function get(): string
    {
        return $this->id;
    }

    public function __toString(): string
    {
        return $this->id;
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && (string)$this === (string)$other;
    }
}
