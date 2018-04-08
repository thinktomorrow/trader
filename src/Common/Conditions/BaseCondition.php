<?php

namespace Thinktomorrow\Trader\Common\Conditions;

use Thinktomorrow\Trader\Common\Contracts\HasParameters;
use Thinktomorrow\Trader\Common\Contracts\HasType;
use Thinktomorrow\Trader\Common\Helpers\HandlesParameters;
use Thinktomorrow\Trader\Common\Helpers\HandlesType;

abstract class BaseCondition implements HasParameters, HasType
{
    use HandlesParameters, HandlesType;

    protected $parameters = [];

    public function __construct(string $type = null)
    {
        $this->type = $type ?? $this->guessType();
    }
}
