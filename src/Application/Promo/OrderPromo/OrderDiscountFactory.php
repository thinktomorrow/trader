<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo;

use Thinktomorrow\Trader\Application\Promo\LinePromo\LineDiscount;
use Thinktomorrow\Trader\Application\VatRate\Allocator\ProRateAllocator;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Common\Map\Factory;

class OrderDiscountFactory extends Factory
{
    private OrderConditionFactory $applicableConditionFactory;

    private VatAllocator $vatAllocator;

    public function __construct(
        array $mapping,
        OrderConditionFactory $applicableConditionFactory,
        ?VatAllocator $vatAllocator = null,
    ) {
        parent::__construct($mapping);
        $this->applicableConditionFactory = $applicableConditionFactory;
        $this->vatAllocator = $vatAllocator ?? new VatAllocator(new ProRateAllocator);
    }

    public function make(string $key, array $state, array $aggregateState, $conditionStates): OrderDiscount|LineDiscount
    {
        $conditions = array_map(fn ($conditionState) => $this->applicableConditionFactory->make($conditionState['key'], $conditionState, $state), $conditionStates);

        return $this->findMappable($key)::fromMappedData($state, $aggregateState, $conditions, $this->vatAllocator);
    }
}
