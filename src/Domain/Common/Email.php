<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common;

use Assert\Assertion;

class Email
{
    private string $email;

    private function __construct(string $email)
    {
        Assertion::email($email);

        $this->email = $email;
    }

    public static function fromString(string $email): static
    {
        return new static($email);
    }

    public function get(): string
    {
        return $this->email;
    }

    public function equals($other): bool
    {
        return (get_class($other) === get_class($this) && $other->email === $this->email);
    }
}
