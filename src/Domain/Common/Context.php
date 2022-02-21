<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common;

use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;

final class Context
{
    public function getDefaultShippingId(): ShippingId
    {
        return ShippingId::fromString('bpost_home');
    }

    public function getDefaultShippingCountry(): string
    {
        return 'BE';
    }

    public function getDate(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
