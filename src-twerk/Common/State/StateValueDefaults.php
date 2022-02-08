<?php


namespace Thinktomorrow\Trader\Common\State;

trait StateValueDefaults
{
    protected string $state;

    private function __construct(string $state)
    {
        $reflection = new \ReflectionClass($this);
        if (! in_array($state, $reflection->getConstants())) {
            throw new \InvalidArgumentException($state . ' is not a valid '. class_basename($this) .' state.');
        }

        $this->state = $state;
    }

    public static function fromObject(Stateful $object, string $stateKey)
    {
        if(is_null($object->getState($stateKey))) {
            throw StateException::missingStateValue('Stateful object ['.get_class($object).'] has no state value for ' . $stateKey);
        }

        return new static($object->getState($stateKey));
    }

    public static function fromString(string $state)
    {
        return new static($state);
    }

    public function get(): string
    {
        return $this->state;
    }

    public function is(string $state): bool
    {
        return $this->state === $state;
    }
}
