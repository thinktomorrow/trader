<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicablePromo;

use Thinktomorrow\Trader\Domain\Common\Map\HasMapping;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;

final class ConditionFactory
{
    use HasMapping;

    public function __construct(array $mapping)
    {
        $this->setMapping($mapping);
    }

    public function make(string $key, array $state, array $aggregateState): Condition
    {
        return $this->findMappable($key)::fromMappedData($state, $aggregateState);
    }
}
