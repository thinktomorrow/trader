<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Customer;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

class UpdateLocale
{
    private string $customerId;
    private string $iso15897Locale;

    public function __construct(string $customerId, string $iso15897Locale)
    {
        $this->customerId = $customerId;
        $this->iso15897Locale = $iso15897Locale;
    }

    public function getCustomerId(): CustomerId
    {
        return CustomerId::fromString($this->customerId);
    }

    public function getLocale(): Locale
    {
        return Locale::fromString($this->iso15897Locale);
    }
}
