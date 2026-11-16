<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Promo\Discounts;

use Thinktomorrow\Trader\Domain\Common\Map\Factory;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\ConditionFactory;

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
