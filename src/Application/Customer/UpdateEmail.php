<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Customer;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

class UpdateEmail
{
    private string $customerId;
    private string $oldEmail;
    private string $newEmail;

    public function __construct(string $customerId, string $oldEmail, string $newEmail)
    {
        $this->customerId = $customerId;
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
    }

    public function getCustomerId(): CustomerId
    {
        return CustomerId::fromString($this->customerId);
    }

    public function getOldEmail(): Email
    {
        return Email::fromString($this->oldEmail);
    }

    public function getNewEmail(): Email
    {
        return Email::fromString($this->newEmail);
    }
}
