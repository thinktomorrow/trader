<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Models\Cart;

use Thinktomorrow\Trader\Application\Cart\Read\CartShopper;
use Thinktomorrow\Trader\Application\Common\RendersData;

class DefaultCartShopper implements CartShopper
{
    use RendersData;

    protected string $shopper_id;
    protected ?string $customer_id;
    protected string $email;
    protected bool $is_business;
    protected array $data;

    private function __construct()
    {
    }

    public static function fromMappedData(array $state, array $cartState): static
    {
        $shopper = new static();

        $shopper->shopper_id = $state['shopper_id'];
        $shopper->customer_id = $state['customer_id'];
        $shopper->email = $state['email'];
        $shopper->is_business = $state['is_business'];
        $shopper->data = json_decode($state['data'], true);

        return $shopper;
    }

    public function getShopperId(): string
    {
        return $this->shopper_id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function isCustomer(): bool
    {
        return ! is_null($this->customer_id);
    }

    public function isGuest(): bool
    {
        return ! $this->isCustomer();
    }

    public function isBusiness(): bool
    {
        return $this->is_business;
    }
}
