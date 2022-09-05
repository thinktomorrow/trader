<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\CustomerLogin\Events;

use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

class PasswordChanged
{
    public function __construct(public readonly CustomerId $customerId)
    {
    }
}
