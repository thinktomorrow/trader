<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Customer;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;

class Customer implements Aggregate
{
    use RecordsEvents;
    use HasData;

    public readonly CustomerId $customerId;
    private Email $email;
    private bool $isBusiness;

    private function __construct()
    {
    }

    public static function create(CustomerId $customerId, Email $email, bool $isBusiness)
    {
        $customer = new static();
        $customer->customerId = $customerId;
        $customer->email = $email;
        $customer->isBusiness = $isBusiness;

        return $customer;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
    }

    public function updateBusiness(bool $isBusiness): void
    {
        $this->isBusiness = $isBusiness;
    }

    public function isBusiness(): bool
    {
        return $this->isBusiness;
    }

    public function getMappedData(): array
    {
        return [
            'customer_id' => $this->customerId->get(),
            'email' => $this->email->get(),
            'is_business' => $this->isBusiness,
            'data' => json_encode($this->data),
        ];
    }

    public function getChildEntities(): array
    {
        return [];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $customer = new static();
        $customer->customerId = $state['customer_id'] ? CustomerId::fromString($state['customer_id']) : null;
        $customer->isBusiness = ! ! $state['is_business'];
        $customer->email = Email::fromString($state['email']);
        $customer->data = json_decode($state['data'], true);

        return $customer;
    }
}
