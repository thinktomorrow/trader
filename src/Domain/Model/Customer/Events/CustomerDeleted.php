<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Customer\Events;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

class CustomerDeleted
{
    public function __construct(public readonly CustomerId $customerId, public readonly Email $email)
    {
    }
}
