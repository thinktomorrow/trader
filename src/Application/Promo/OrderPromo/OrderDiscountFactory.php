<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo;

use Thinktomorrow\Trader\Domain\Common\Map\Factory;

class OrderDiscountFactory extends Factory
{
    private OrderConditionFactory $applicableConditionFactory;

    public function __construct(array $mapping, OrderConditionFactory $applicableConditionFactory)
    {
        parent::__construct($mapping);
        $this->applicableConditionFactory = $applicableConditionFactory;
    }

    public function make(string $key, array $state, array $aggregateState, $conditionStates): OrderDiscount
    {
        $conditions = array_map(fn ($conditionState) => $this->applicableConditionFactory->make($conditionState['key'], $conditionState, $state), $conditionStates);

        return $this->findMappable($key)::fromMappedData($state, $aggregateState, $conditions);
    }
}
