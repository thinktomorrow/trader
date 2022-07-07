<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\State;

interface State
{
    public function getValueAsString(): string;
}
