<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Domain\Conditions\ItemCondition;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\ConditionKey;
use Thinktomorrow\Trader\Discounts\Domain\Types\TypeKey;

// TODO this could be using same common code as ShippingRuleFactory
class DiscountFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Collection of condition instances
     *
     * @var array
     */
    private $conditions = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create($id, string $type, array $conditions, array $adjusters)
    {
        foreach($conditions as $condition => $value)
        {
            /**
             * If condition does not map to a condition class it is an option value so
             * just skip it and use it as parameter value for our condition instances
             */
            try{
                $this->conditions[] = $this->resolveConditionClass($this->getConditionClassName($condition), $conditions);
            }
            catch(\InvalidArgumentException $e)
            {
                continue;
            }
        }

        $discountClass = $this->getDiscountClassName($type);

        return new $discountClass(
            DiscountId::fromInteger($id),
            $this->conditions,
            $adjusters
        );
    }

    private function getDiscountClassName(string $type): string
    {
        return TypeKey::fromString($type)->class();
    }

    private function getConditionClassName(string $condition): string
    {
        return ConditionKey::fromString($condition)->class();
    }

    /**
     * @param $conditionClass
     * @param array $parameters
     * @return Condition
     */
    private function resolveConditionClass($conditionClass, array $parameters): Condition
    {
        $instance = $this->container->get($conditionClass);
        $instance->setParameters($parameters);

        return $instance;
    }
}