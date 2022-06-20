<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicablePromo;

use Thinktomorrow\Trader\Domain\Common\Map\Factory;

class ApplicableDiscountFactory extends Factory
{
    private ApplicableConditionFactory $applicableConditionFactory;

    public function __construct(array $mapping, ApplicableConditionFactory $applicableConditionFactory)
    {
        parent::__construct($mapping);
        $this->applicableConditionFactory = $applicableConditionFactory;
    }

    public function make(string $key, array $state, array $aggregateState, $conditionStates): ApplicableDiscount
    {
        $conditions = array_map(fn ($conditionState) => $this->applicableConditionFactory->make($conditionState['key'], $conditionState, $state), $conditionStates);

        return $this->findMappable($key)::fromMappedData($state, $aggregateState, [ApplicableCondition::class => $conditions]);
    }
}
