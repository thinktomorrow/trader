<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

/**
 * Description for an applied discount
 */
class DiscountDescription
{
    private $type;

    private $values = [];

    public function __construct(string $type, array $values)
    {
        $this->type = $type;
        $this->values = $values;
    }

    public function get(): string
    {
        // TODO this should return the (translated) description
        return $this->type;
    }

    public function type(): string
    {
        // Get description type - to be used for language keys
        // THIS SHOULD CORRESPOND WITH THE Discount TYPES
        // e.g. percentage_off => %s off on second %s product
        return $this->type;
    }

    public function values(): array
    {
        // Values to be used to fill the language placeholders
        return $this->values;
    }

    public function __toString()
    {
        return $this->get();
    }
}