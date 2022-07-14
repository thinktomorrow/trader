<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Customer;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;

class RegisterCustomer
{
    private string $email;
    private bool $isBusiness;
    private string $iso15897Locale;
    private array $data;

    public function __construct(string $email, bool $isBusiness, string $iso15897Locale, array $data)
    {
        $this->email = $email;
        $this->isBusiness = $isBusiness;
        $this->iso15897Locale = $iso15897Locale;
        $this->data = $data;
    }

    public function getEmail(): Email
    {
        return Email::fromString($this->email);
    }

    public function isBusiness(): bool
    {
        return $this->isBusiness;
    }

    public function getLocale(): Locale
    {
        return Locale::fromString($this->iso15897Locale);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
