<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain;

use Thinktomorrow\Trader\Common\State\StateMachine;

interface ProductStateMachine extends StateMachine
{
    public function isAvailable(): bool;

    public static function getAvailableStates(): array;
}
