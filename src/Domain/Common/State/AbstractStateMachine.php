<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\State;

abstract class AbstractStateMachine
{
    protected array $states;
    protected array $transitions;

    public function __construct(array $states, array $transitions)
    {
        $this->states = $states;
        $this->transitions = $transitions;

        $this->validateTransitions();
    }

    abstract protected function getState($model): State;
    abstract protected function updateState($model, State $state, array $data): void;

    public function can($model, $transition): bool
    {
        // Array of transitions passed means we check that at least one is allowed
        if (is_array($transition)) {
            return count(array_intersect($transition, $this->getAllowedTransitions($model))) > 0;
        }

        if (! in_array($transition, $this->getAllowedTransitions($model))) {
            return false;
        }

        if (! in_array($this->getState($model), $this->transitions[$transition]['from'])) {
            return false;
        }

        return true;
    }

    public function assertNewState($model, $state)
    {
        if (! $this->canTransitionTo($model, $state)) {
            throw StateException::invalidState($state, $this->getState($model));
        }
    }

    public function getAllowedTransitions($model): array
    {
        $transitions = [];

        foreach ($this->transitions as $transitionKey => $transition) {
            if (in_array($this->getState($model), $transition['from'])) {
                $transitions[] = $transitionKey;
            }
        }

        return $transitions;
    }

    public function apply($model, string $transition, array $data = []): void
    {
        if (! $this->can($model, $transition)) {
            throw StateException::invalidTransition($transition, $this->getState($model)?->getValueAsString());
        }

        $state = $this->transitions[$transition]['to'];

        $this->updateState($model, $state, $data);
    }

    /**
     * Verify the new state is valid.
     *
     * @param $state
     *
     * @return bool
     */
    public function canTransitionTo($model, $state)
    {
        if (! in_array($state, $this->states)) {
            return false;
        }

        foreach ($this->transitions as $transition) {
            if (! in_array($this->getState($model), $transition['from'])) {
                continue;
            }

            if ($transition['to'] == $state) {
                return true;
            }
        }

        return false;
    }

    private function validateTransitions(): void
    {
        foreach ($this->transitions as $transitionKey => $transition) {
            if (! isset($transition['from']) || ! isset($transition['to']) || ! is_array($transition['from'])) {
                throw StateException::malformedTransition($transitionKey);
            }

            foreach ($transition['from'] as $fromState) {
                if (! $fromState instanceof State) {
                    throw StateException::transitionStateIsNotAsStateInstance($transitionKey);
                }
                if (! in_array($fromState, $this->states)) {
                    throw StateException::invalidTransitionState($transitionKey, $fromState->getValueAsString());
                }
            }

            if (! $transition['to'] instanceof State) {
                throw StateException::transitionStateIsNotAsStateInstance($transitionKey);
            }

            if (! in_array($transition['to'], $this->states)) {
                throw StateException::invalidTransitionState($transitionKey, $transition['to']->getValueAsString());
            }
        }
    }
}
