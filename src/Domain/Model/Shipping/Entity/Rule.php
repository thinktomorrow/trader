<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Shipping\Entity;

use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Order\Price\SubTotal;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingTotal;

final class Rule
{
    private ShippingId $shippingId;
    private ShippingTotal $cost;
    private SubTotal $from;
    private SubTotal $to;
    private array $countries;

    private function __construct()
    {

    }

    public static function create(ShippingId $shippingId, ShippingTotal $cost, SubTotal $from, SubTotal $to, array $countries): static
    {
        $rule = new static();

        $rule->shippingId = $shippingId;
        $rule->cost = $cost;
        $rule->from = $from;
        $rule->to = $to;
        $rule->countries = $countries;

        return $rule;
    }

    public function update(ShippingTotal $cost, SubTotal $from, SubTotal $to, array $countries): void
    {
        $this->cost = $cost;
        $this->from = $from;
        $this->to = $to;
        $this->countries = $countries;
    }

    public function getCost(): ShippingTotal
    {
        return $this->cost;
    }
}
