<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Order\Merchant;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class UpdateShopper
{
    private string $orderId;
    private string $email;
    private bool $is_business;
    private string $locale;
    private array $data;

    public function __construct(string $orderId, string $email, bool $is_business, string $locale, array $data)
    {
        $this->orderId = $orderId;

        $this->email = $email;
        $this->is_business = $is_business;
        $this->locale = $locale;
        $this->data = $data;
    }

    public function getOrderId(): OrderId
    {
        return OrderId::fromString($this->orderId);
    }

    public function getEmail(): Email
    {
        return Email::fromString($this->email);
    }

    public function isBusiness(): bool
    {
        return $this->is_business;
    }

    public function getLocale(): Locale
    {
        return Locale::fromString($this->locale);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
