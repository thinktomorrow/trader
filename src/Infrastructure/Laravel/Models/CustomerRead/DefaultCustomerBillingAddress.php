<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\CustomerRead;

use Thinktomorrow\Trader\Application\Customer\Read\CustomerBillingAddress;

final class DefaultCustomerBillingAddress extends Address implements CustomerBillingAddress
{
}
