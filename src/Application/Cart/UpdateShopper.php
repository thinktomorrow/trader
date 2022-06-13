<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class UpdateShopper
{
    private string $orderId;
    private string $email;
    private bool $is_business;
    private array $data;

    public function __construct(string $orderId, string $email, bool $is_business, array $data)
    {
        $this->orderId = $orderId;

        $this->email = $email;
        $this->is_business = $is_business;
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

    public function getData(): array
    {
        return $this->data;
    }
}
