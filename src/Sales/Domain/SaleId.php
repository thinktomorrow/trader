<?php

namespace Thinktomorrow\Trader\Sales\Domain;

use Thinktomorrow\Trader\Common\Domain\AggregateId;

class SaleId
{
    use AggregateId;

    /**
     * Allow to create placeholder itemId. Item is assigned an id after being stored
     * Before this, a placeholder is required for the domain
     */
    public static function placeholder()
    {
        return new static('xxx');
    }
}
