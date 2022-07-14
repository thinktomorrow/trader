<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Customer;

use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

class UpdateData
{
    private string $customerId;
    private array $data;

    public function __construct(string $customerId, array $data)
    {
        $this->customerId = $customerId;
        $this->data = $data;
    }

    public function getCustomerId(): CustomerId
    {
        return CustomerId::fromString($this->customerId);
    }

    public function getData(): array
    {
        return $this->data;
    }
}
