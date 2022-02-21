<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Customer;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;

class Customer implements Aggregate
{
    public readonly CustomerId $customerId;
    private Email $email;
    private string $firstname;
    private string $lastname;

    private function __construct()
    {

    }

    public static function create(CustomerId $customerId, Email $email, string $firstname, string $lastname)
    {
        $customer = new static();
        $customer->customerId = $customerId;
        $customer->email = $email;
        $customer->firstname = $firstname;
        $customer->lastname = $lastname;

        return $customer;
    }

    public function getMappedData(): array
    {
        return [
            'customer_id' => $this->customerId->get(),
            'email'       => $this->email->get(),
            'firstname'   => $this->firstname,
            'lastname'    => $this->lastname,
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
        $customer->email = Email::fromString($state['email']);
        $customer->firstname = $state['firstname'];
        $customer->lastname = $state['lastname'];

        return $customer;
    }
}
