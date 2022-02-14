<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order\Details;

use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingTotal;
use Thinktomorrow\Trader\Domain\Model\Shipping\ShippingState;

final class Shipping
{
    public readonly ShippingId $shippingId;
    public readonly ShippingState $shippingState;
    public readonly ShippingTotal $shippingTotal;

    private function __construct(){}

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $shipping = new static();

        $shipping->shippingId = ShippingId::fromString($state['id']);
        $shipping->shippingState = ShippingState::from($state['state']);
        $shipping->shippingTotal = ShippingTotal::fromScalars($state['cost'], 'EUR', $state['tax_rate'], $state['includes_vat']);

        return $shipping;
    }
}
