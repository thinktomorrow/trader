<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo;

use Thinktomorrow\Trader\Domain\Common\Map\Factory;

class OrderConditionFactory extends Factory
{
    public function make(string $key, array $state, array $aggregateState): OrderCondition
    {
        return $this->findMappable($key)::fromMappedData($state, $aggregateState);
    }
}
