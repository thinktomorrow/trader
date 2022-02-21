<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

class Shopper implements ChildEntity
{
    private Email $email;
    private string $firstname;
    private string $lastname;
    private ?CustomerId $customerId = null;

    /**
     * Flag to indicate that this guest shopper
     * wants a customer account for next time.
     */
    private bool $registerAfterCheckout = false;

    private function __construct()
    {

    }

    public static function create(Email $email, string $firstname, string $lastname): static
    {
        // locale, preferences, customer -> fixed discounts, email, firstname, lastname
        $shopper = new static();
        $shopper->email = $email;
        $shopper->firstname = $firstname;
        $shopper->lastname = $lastname;

        return $shopper;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getCustomerId(): ?CustomerId
    {
        return $this->customerId;
    }

    public function updateCustomerId(CustomerId $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function updateRegisterAfterCheckout(bool $registerAfterCheckout): void
    {
        $this->registerAfterCheckout = $registerAfterCheckout;
    }

    public function registerAfterCheckout(): bool
    {
        return $this->registerAfterCheckout && !is_null($this->customerId);
    }

    public function deleteCustomerId(): void
    {
        $this->customerId = null;
    }

    public function getMappedData(): array
    {
        return [
            'email'                   => $this->email->get(),
            'firstname'               => $this->firstname,
            'lastname'                => $this->lastname,
            'register_after_checkout' => $this->registerAfterCheckout,
            'customer_id'             => $this->customerId?->get(),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $shopper = new static();
        $shopper->email = Email::fromString($state['email']);
        $shopper->firstname = $state['firstname'];
        $shopper->lastname = $state['lastname'];
        $shopper->registerAfterCheckout = $state['register_after_checkout'];
        $shopper->customerId = $state['customer_id'] ? CustomerId::fromString($state['customer_id']) : null;

        return $shopper;
    }
}
