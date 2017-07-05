<?php
declare(strict_types = 1);

namespace Thinktomorrow\Trader\Common\Domain;

trait AggregateId
{
    /**
     * @var string
     */
    private $id;

    private function __construct(int $id)
    {
        $this->id = $id;
    }

    /**
     * @param int $id
     * @return static
     */
    public static function fromInteger(int $id)
    {
        return new static($id);
    }

    public function get(): int
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
            && (int)$this->get() === (int)$otherAggregateId->get();
    }
}