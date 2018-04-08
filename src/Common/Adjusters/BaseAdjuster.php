<?php

namespace Thinktomorrow\Trader\Common\Adjusters;

use Thinktomorrow\Trader\Common\Contracts\HasParameters;
use Thinktomorrow\Trader\Common\Contracts\HasType;
use Thinktomorrow\Trader\Common\Helpers\HandlesParameters;
use Thinktomorrow\Trader\Common\Helpers\HandlesType;

abstract class BaseAdjuster implements HasParameters, HasType
{
    use HandlesParameters, HandlesType;

    protected $parameters = [];

    public function __construct(string $type = null)
    {
        $this->type = $type ?? $this->guessType();
    }
}
