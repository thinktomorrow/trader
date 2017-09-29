<?php

namespace Thinktomorrow\Trader\Payment\Domain;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Payment\Domain\Conditions\Country;
use Thinktomorrow\Trader\Payment\Domain\Conditions\MaximumAmount;
use Thinktomorrow\Trader\Payment\Domain\Conditions\MinimumAmount;

class PaymentRuleFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    private static $conditionMapping = [
        'country'        => Country::class,
        'minimum_amount' => MinimumAmount::class,
        'maximum_amount' => MaximumAmount::class,
    ];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create($id, array $conditions, array $adjusters)
    {
        return new PaymentRule(
            PaymentRuleId::fromInteger($id),
            $this->createConditions($conditions),
            $adjusters
        );
    }

    /**
     * @param $condition
     * @param array $parameters
     *
     * @return Condition
     */
    private function resolveConditionClass($condition, array $parameters): Condition
    {
        $class = self::$conditionMapping[$condition];

        $instance = ($this->container->has($class))
            ? $this->container->get($class)
            : new $class;

        $instance->setParameters($parameters);

        return $instance;
    }

    /**
     * @param array $conditions
     * @return array
     */
    private function createConditions(array $conditions): array
    {
        $conditionObjects = [];

        foreach ($conditions as $condition => $value) {
            /*
             * If condition does not map to a condition class it is an option value so
             * just skip it and use it as parameter value for our condition instances
             */
            if (!isset(self::$conditionMapping[$condition])) {
                continue;
            }

            $conditionObjects[] = $this->resolveConditionClass($condition, $conditions);
        }

        return $conditionObjects;
    }
}
