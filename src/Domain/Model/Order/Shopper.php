<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Common\Entity\HasData;
use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

class Shopper implements ChildEntity
{
    use HasData;

    public readonly ShopperId $shopperId;
    private Email $email;
    private ?CustomerId $customerId = null;
    private bool $isBusiness;

    /**
     * Flag to indicate that this guest shopper
     * wants a customer account for next time.
     */
    private bool $registerAfterCheckout = false;

    private function __construct()
    {

    }

    public static function create(ShopperId $shopperId, Email $email, bool $isBusiness): static
    {
        $shopper = new static();
        $shopper->shopperId = $shopperId;
        $shopper->email = $email;
        $shopper->isBusiness = $isBusiness;

        return $shopper;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getCustomerId(): ?CustomerId
    {
        return $this->customerId;
    }

    public function updateCustomerId(CustomerId $customerId): void
    {
        $this->customerId = $customerId;
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
            'shopper_id'              => $this->shopperId->get(),
            'email'                   => $this->email->get(),
            'is_business'             => $this->isBusiness,
            'register_after_checkout' => $this->registerAfterCheckout,
            'customer_id'             => $this->customerId?->get(),
            'data'                    => json_encode($this->data),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $shopper = new static();
        $shopper->shopperId = ShopperId::fromString($state['shopper_id']);
        $shopper->email = Email::fromString($state['email']);
        $shopper->isBusiness = $state['is_business'];
        $shopper->registerAfterCheckout = $state['register_after_checkout'];
        $shopper->customerId = $state['customer_id'] ? CustomerId::fromString($state['customer_id']) : null;
        $shopper->data = json_decode($state['data'], true);

        return $shopper;
    }
}
