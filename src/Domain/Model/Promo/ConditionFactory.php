<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo;

use Thinktomorrow\Trader\Domain\Common\Map\Factory;

final class ConditionFactory extends Factory
{
    public function make(string $key, array $state, array $aggregateState): Condition
    {
        return $this->findMappable($key)::fromMappedData($state, $aggregateState);
    }
}
