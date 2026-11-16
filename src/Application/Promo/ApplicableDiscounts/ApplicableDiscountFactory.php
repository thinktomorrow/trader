<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts;

use Thinktomorrow\Trader\Application\Promo\ApplicableConditions\ApplicableConditionFactory;
use Thinktomorrow\Trader\Application\VatRate\Allocator\ProRateAllocator;
use Thinktomorrow\Trader\Application\VatRate\Allocator\VatAllocator;
use Thinktomorrow\Trader\Domain\Common\Map\Factory;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;

class ApplicableDiscountFactory extends Factory
{
    private ApplicableConditionFactory $applicableConditionFactory;

    private VatAllocator $vatAllocator;

    public function __construct(
        array $mapping,
        ApplicableConditionFactory $applicableConditionFactory,
        ?VatAllocator $vatAllocator = null,
    ) {
        parent::__construct($mapping);
        $this->applicableConditionFactory = $applicableConditionFactory;
        $this->vatAllocator = $vatAllocator ?? new VatAllocator(new ProRateAllocator);
    }

    public function make(string $key, array $state, array $aggregateState, $conditionStates): ApplicableDiscount
    {
        $conditions = array_map(fn ($conditionState) => $this->applicableConditionFactory->make($conditionState['key'], $conditionState, $state), $conditionStates);

        return $this->findMappable($key)::fromMappedData($state, $aggregateState, $conditions, $this->vatAllocator);
    }

    /** @return list<ApplicableDiscount> */
    public function forPromo(Promo $promo): array
    {
        return array_map(fn (Discount $discount): ApplicableDiscount => $this->make(
            $discount::getMapKey(),
            $discount->getMappedData(),
            $promo->getMappedData(),
            array_map(fn (Condition $condition): array => $condition->getMappedData(), $discount->getConditions()),
        ), $promo->getDiscounts());
    }
}
