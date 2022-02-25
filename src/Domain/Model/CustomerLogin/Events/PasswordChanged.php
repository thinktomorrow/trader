<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\CustomerLogin\Events;

use Thinktomorrow\Trader\Domain\Common\Email;

class PasswordChanged
{
    public readonly Email $email;

    public function __construct(Email $email)
    {
        $this->email = $email;
    }
}
