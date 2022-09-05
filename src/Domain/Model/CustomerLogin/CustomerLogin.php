<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\CustomerLogin;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Entity\Aggregate;
use Thinktomorrow\Trader\Domain\Common\Event\RecordsEvents;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\CustomerLogin\Events\PasswordChanged;

class CustomerLogin implements Aggregate
{
    use RecordsEvents;

    public readonly CustomerId $customerId;
    private Email $email;
    private string $password;

    public static function create(CustomerId $customerId, Email $email, string $password): static
    {
        $customerLogin = new static();
        $customerLogin->customerId = $customerId;
        $customerLogin->email = $email;
        $customerLogin->password = $password;

        return $customerLogin;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function changePassword(string $password): void
    {
        $this->password = $password;

        $this->recordEvent(new PasswordChanged($this->customerId));
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getMappedData(): array
    {
        return [
            'email' => $this->email->get(),
            'password' => $this->password,
        ];
    }

    public static function fromMappedData(array $state, array $childEntities = []): static
    {
        $customerLogin = new static();
        $customerLogin->customerId = CustomerId::fromString($state['customer_id']);
        $customerLogin->email = Email::fromString($state['email']);
        $customerLogin->password = $state['password'];

        return $customerLogin;
    }

    public function getChildEntities(): array
    {
        return [];
    }
}
