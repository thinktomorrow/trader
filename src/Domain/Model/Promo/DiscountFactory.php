<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo;

use Thinktomorrow\Trader\Domain\Common\Map\Factory;

class DiscountFactory extends Factory
{
    private ConditionFactory $conditionFactory;

    public function __construct(array $mapping, ConditionFactory $conditionFactory)
    {
        parent::__construct($mapping);
        $this->conditionFactory = $conditionFactory;
    }

    public function make(string $key, array $state, array $aggregateState, $conditionStates): Discount
    {
        $conditions = array_map(fn ($conditionState) => $this->conditionFactory->make($conditionState['key'], $conditionState, $state), $conditionStates);

        return $this->findMappable($key)::fromMappedData($state, $aggregateState, [Condition::class => $conditions]);
    }
}
