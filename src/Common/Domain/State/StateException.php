<?php

namespace Thinktomorrow\Trader\Common\Domain\State;

class StateException extends \Exception
{
    public static function malformedTransition($transition, $stateMachine)
    {
        return new self('Transition ['.$transition.'] is malformed on '.get_class($stateMachine).'. It should contain both a [from:array] and [to:string] value.');
    }

    public static function invalidTransitionKey($transition, $stateMachine)
    {
        return new self('unknown transition ['.$transition.'] on '.get_class($stateMachine));
    }

    public static function invalidTransition($transition, $state, $stateMachine)
    {
        return new self('Transition ['.$transition.'] cannot be applied from current state ['.$state.'] on '.get_class($stateMachine));
    }

    public static function invalidState($transition, $state, $stateMachine)
    {
        return new self('Transition ['.$transition.'] contains a non existing ['.$state.'] on '.get_class($stateMachine));
    }
}