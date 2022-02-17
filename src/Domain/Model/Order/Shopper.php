<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Model\Order;

use Thinktomorrow\Trader\Domain\Common\Entity\ChildEntity;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;

class Shopper implements ChildEntity
{
    private ?CustomerId $customerId = null;

    private function __construct()
    {

    }

    public static function create(): static
    {
        $shopper = new static();

        return $shopper;
    }

    public function getCustomerId(): ?CustomerId
    {
        return $this->customerId;
    }

    public function updateCustomerId(CustomerId $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function deleteCustomerId(): void
    {
        $this->customerId = null;
    }

    public function getMappedData(): array
    {
        return [
            'customer_id' => $this->customerId?->get(),
        ];
    }

    public static function fromMappedData(array $state, array $aggregateState): static
    {
        $shopper = new static();
        $shopper->customerId = $state['customer_id'] ? CustomerId::fromString($state['customer_id']) : null;

        return $shopper;
    }
}
