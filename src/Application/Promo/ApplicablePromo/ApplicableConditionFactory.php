<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicablePromo;

use Thinktomorrow\Trader\Domain\Common\Map\Factory;

class ApplicableConditionFactory extends Factory
{
    public function make(string $key, array $state, array $aggregateState): ApplicableCondition
    {
        return $this->findMappable($key)::fromMappedData($state, $aggregateState);
    }
}
