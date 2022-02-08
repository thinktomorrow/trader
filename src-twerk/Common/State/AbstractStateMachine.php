<?php

namespace Thinktomorrow\Trader\Common\State;

abstract class AbstractStateMachine implements StateMachine
{
    protected Stateful $object;

    public function __construct(Stateful $object)
    {
        $this->object = $object;

        $this->validateConstraints();
    }

    abstract protected function getStates(): array;
    abstract protected function getTransitions(): array;

    public function getState(): string
    {
        return $this->object->getState($this->getStateKey());
    }

    protected function getStateKey(): string
    {
        return 'state';
    }

    /**
     * @param string $transition
     * @throws StateException
     */
    public function apply(string $transition): void
    {
        if (! array_key_exists($transition, $this->getTransitions())) {
            throw StateException::invalidTransitionKey($transition, $this);
        }

        if (! in_array($this->getState(), $this->getTransitions()[$transition]['from'])) {
            throw StateException::invalidTransition($transition, $this->getState(), $this);
        }

        $state = $this->getTransitions()[$transition]['to'];

        $this->object->changeState($this->getStateKey(), $state);
    }

    public function assertNewState(string $state)
    {
        if (! $this->canTransitionTo($state)) {
            throw StateException::invalidState($state, $this->getState(), $this);
        }
    }

    public function canTransitionTo(string $state): bool
    {
        if (! in_array($state, $this->getStates())) {
            return false;
        }

        foreach ($this->getTransitions() as $transition) {
            if (! in_array($this->getState(), $transition['from'])) {
                continue;
            }

            if ($transition['to'] == $state) {
                return true;
            }
        }

        return false;
    }

    public function getAllowedTransitions(): array
    {
        $allowedTransitions = [];

        foreach ($this->getTransitions() as $transitionKey => $transition) {
            if (! in_array($this->getState(), $transition['from'])) {
                continue;
            }

            $allowedTransitions[$transitionKey] = $transition;
        }

        return $allowedTransitions;
    }

    /** @throws StateException */
    private function validateConstraints(): void
    {
        foreach ($this->getTransitions() as $transitionKey => $transition) {
            if (! isset($transition['from']) || ! isset($transition['to']) || ! is_array($transition['from'])) {
                throw StateException::malformedTransition($transitionKey, $this);
            }

            foreach ($transition['from'] as $fromState) {
                if (! in_array($fromState, $this->getStates())) {
                    throw StateException::invalidTransitionState($transitionKey, $fromState, $this);
                }
            }

            if (! in_array($transition['to'], $this->getStates())) {
                throw StateException::invalidTransitionState($transitionKey, $transition['to'], $this);
            }
        }
    }
}
