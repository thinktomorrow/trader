<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Customer\Exceptions;

use Thinktomorrow\Trader\Domain\Common\Email;

final class CustomerAlreadyExists extends \InvalidArgumentException
{
    public static function forEmail(Email $email): self
    {
        return new self('A customer with email ['.$email->get().'] already exists.');
    }
}
