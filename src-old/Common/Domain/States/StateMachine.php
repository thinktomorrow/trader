<?php

namespace Common\Domain\States;

abstract class StateMachine
{
    /**
     * States and transitions should be set on the specific state Machine.
     *
     * @var array
     */
    protected $states = [];

    /**
     * Transitions from one state to other(s)
     * @var array
     */
    protected $transitions = [];

    /** @var StatefulContract */
    protected $statefulContract;

    /** @var string */
    private $stateKey;

    public function __construct(StatefulContract $statefulContract, string $stateKey)
    {
        $this->statefulContract = $statefulContract;
        $this->stateKey = $stateKey;

        $this->validateTransitions();
    }

    public function apply($transition)
    {
        // Check valid transition request
        if (!array_key_exists($transition, $this->transitions)) {
            throw StateException::invalidTransitionKey($transition, $this);
        }

        if (!in_array($this->statefulContract->stateOf($this->stateKey), $this->transitions[$transition]['from'])) {
            throw StateException::invalidTransition($transition, $this->statefulContract->stateOf($this->stateKey), $this);
        }

        $state = $this->transitions[$transition]['to'];

        $this->statefulContract->changeStateOf($this->stateKey, $state);
    }

    /**
     * assert the integrity of the new state.
     *
     * @param StatefulContract $statefulContract
     * @param string $stateKey
     * @param $state
     *
     * @throws StateException
     */
    public static function assertNewState(StatefulContract $statefulContract, string $stateKey, $state)
    {
        $machine = new static($statefulContract, $stateKey);

        if (!$machine->canTransitionTo($state)) {
            throw StateException::invalidState($state, $statefulContract->stateOf($stateKey), $machine);
        }
    }

    /**
     * Verify the new state is valid.
     *
     * @param $state
     *
     * @return bool
     */
    public function canTransitionTo($state)
    {
        if (!in_array($state, $this->states)) {
            return false;
        }

        foreach ($this->transitions as $transition) {
            if (!in_array($this->statefulContract->stateOf($this->stateKey), $transition['from'])) {
                continue;
            }

            if ($transition['to'] == $state) {
                return true;
            }
        }

        return false;
    }

    public function allowedTransitions(): array
    {
        $transitions = [];

        foreach($this->transitions as $transitionKey => $transition){
            if (false !== array_search($this->statefulContract->stateOf($this->stateKey), $transition['from'])) {
                $transitions[] = $transitionKey;
            }
        }

        return $transitions;
    }

    private function validateTransitions()
    {
        foreach ($this->transitions as $transitionKey => $transition) {
            if (!array_key_exists('from', $transition) || !array_key_exists('to',$transition) || !is_array($transition['from'])) {
                throw StateException::malformedTransition($transitionKey, $this);
            }

            foreach ($transition['from'] as $fromState) {
                if (!in_array($fromState, $this->states)) {
                    throw StateException::invalidTransitionState($transitionKey, $fromState, $this);
                }
            }

            if (!in_array($transition['to'], $this->states)) {
                throw StateException::invalidTransitionState($transitionKey, $transition['to'], $this);
            }
        }
    }
}