<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class UpdateShopper
{
    private string $orderId;
    private string $email;
    private bool $isBusiness;
    private ?string $vatNumber;
    private string $locale;
    private array $data;

    public function __construct(string $orderId, string $email, bool $isBusiness, ?string $vatNumber, string $locale, array $data)
    {
        $this->orderId = $orderId;

        $this->email = $email;
        $this->isBusiness = $isBusiness;
        $this->vatNumber = $vatNumber;
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
        return $this->isBusiness;
    }

    public function getVatNumber(): ?string
    {
        if (! $this->vatNumber) {
            return null;
        }

        return $this->vatNumber;
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
