<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Customer\Events;

use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

class CustomerHasLoggedOut
{
    public function __construct(public readonly CustomerId $customerId)
    {
    }
}
