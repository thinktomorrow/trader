<?php

namespace Thinktomorrow\Trader\Shipment\Domain;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Shipment\Domain\Conditions\MinimumAmount;

class ShippingRuleFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    private static $conditionMapping = [
        'minimum_amount' => MinimumAmount::class,
    ];

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

    public function create($id, array $conditions, array $adjusters)
    {
        foreach($conditions as $condition => $value)
        {
            /**
             * If condition does not map to a condition class it is an option value so
             * just skip it and use it as parameter value for our condition instances
             */
            if( ! isset(self::$conditionMapping[$condition]) ) continue;

            $this->conditions[] = $this->resolveConditionClass($condition, $conditions);

        }

        return new ShippingRule(ShippingRuleId::fromInteger($id), $this->conditions, $adjusters);
    }

    /**
     * @param $condition
     * @param array $parameters
     * @return Condition
     */
    private function resolveConditionClass($condition, array $parameters): Condition
    {
        $class = self::$conditionMapping[$condition];

        $instance = $this->container->get($class);
        $instance->setParameters($parameters);

        return $instance;
    }
}