<?php

namespace Thinktomorrow\Trader\Common\Domain\State;

abstract class StateMachine
{
    /**
     * States and transitions should be set on the specific state Machine
     *
     * @var array
     */
    protected $states = [];
    protected $transitions = [];

    /**
     * @var StatefulContract
     */
    protected $statefulContract;

    public function __construct(StatefulContract $statefulContract)
    {
        // TODO: add event dispatcher cause here we want to add loads of events no?
        // NO! WE SHOULD BETTER TO THIS ON THE AGGREGATE
        $this->statefulContract = $statefulContract;

        $this->validateTransitions();
    }

    public function apply($transition)
    {
        // Check valid transition request
        if(!array_key_exists($transition,$this->transitions))
        {
            throw StateException::invalidTransitionKey($transition, $this);
        }

        if(!in_array($this->statefulContract->state(),$this->transitions[$transition]['from']))
        {
            throw StateException::invalidTransition($transition, $this->statefulContract->state(), $this);
        }

        $state = $this->transitions[$transition]['to'];

        $this->statefulContract->changeState($state);


    }

    private function validateTransitions()
    {
        foreach ($this->transitions as $transitionKey => $transition) {
            if (!isset($transition['from']) || !isset($transition['to']) || !is_array($transition['from'])) {
                throw StateException::malformedTransition($transitionKey, $this);
            }

            foreach ($transition['from'] as $fromState) {
                if (!in_array($fromState, $this->states)) {
                    throw StateException::invalidState($transitionKey, $fromState, $this);
                }
            }

            if (!in_array($transition['to'], $this->states)) {
                throw StateException::invalidState($transitionKey, $transition['to'], $this);
            }
        }
    }
}