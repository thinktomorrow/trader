<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicablePromo;

use Thinktomorrow\Trader\Domain\Common\Map\HasMapping;

class DiscountFactory
{
    use HasMapping;

    private ConditionFactory $conditionFactory;

    public function __construct(array $mapping, ConditionFactory $conditionFactory)
    {
        $this->setMapping($mapping);
        $this->conditionFactory = $conditionFactory;
    }

    public function make(string $key, array $state, array $aggregateState, $conditionStates): Discount
    {
        $conditions = array_map(fn ($conditionState) => $this->conditionFactory->make($conditionState['type'], $conditionState, $state), $conditionStates);

        return $this->findMappable($key)::fromMappedData($state, $aggregateState, $conditions);
    }
}
