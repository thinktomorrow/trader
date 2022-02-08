<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain;

use Thinktomorrow\Trader\Common\State\StateMachine;

interface ProductGroupStateMachine extends StateMachine
{
    public function isOnline(): bool;

    public static function getOnlineStates(): array;
}
