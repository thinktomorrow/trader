<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Domain\Adjusters;

use Assert\Assertion;

/**
 * Strategy of different actions called adjusters on an object in order to shape it to its final state.
 * The sequence is important as it is handled first to last and each adjuster can influence
 * the object state as it is passed on to the next adjuster.
 */
trait AdjusterStrategy
{
    /**
     * Get all adjuster class names in their sequence
     * @return array
     */
    public function getAdjusters(): array
    {
        return property_exists($this, 'adjusters') ? $this->adjusters : [];
    }

    /**
     * Apply all the given adjusters to the object
     *
     * @param object $object
     * @param array $adjusterInstances
     */
    protected function applyAdjusters(object $object, array $adjusterInstances): void
    {
        Assertion::allImplementsInterface($adjusterInstances, Adjuster::class);

        /** @var Adjuster $adjusterInstance */
        foreach($adjusterInstances as $adjusterInstance) {
            $adjusterInstance->adjust($object);
        }
    }
}
