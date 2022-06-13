<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Address;

enum AddressType: string
{
    case shipping = 'shipping';
    case billing = 'billing';
}
