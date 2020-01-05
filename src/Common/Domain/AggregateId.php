<?php

namespace Thinktomorrow\Trader\Common\Domain;

class AggregateId
{
    /** @var string */
    private $id;

    private function __construct()
    {
    }

    /**
     * @param string $id
     * @return static
     */
    public static function fromString(string $id)
    {
        $aggregateId = new static();
        $aggregateId->id = $id;

        return $aggregateId;
    }

    public function get(): string
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->id;
    }

    public function equals($other): bool
    {
        return get_class($other) === get_class($this)
            && (string)$this === (string)$other;
    }
}
