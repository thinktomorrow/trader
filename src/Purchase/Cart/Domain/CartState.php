<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

class CartState
{
    // Cart is still in customer hands and is still subject to change
    const PENDING = 'pending'; // order still in cart
    const COMMITTED = 'committed'; // cart has got an order record and customer is passed to payment provider
    const ABANDONED = 'abandoned'; // cart has been stale for too long and is considered abandoned by customer
    const REVIVED = 'revived'; // abandoned cart has been revived by customer

    // cart has successfully returned from payment provider and is considered confirmed by customer.
    // Not per se paid yet. From this state on, the cart cannot be altered anymore.
    const CONFIRMED = 'confirmed';

    // The cart has been successfully paid and the cart can be considered an 'order'.
    const PAID = 'paid';

    private $state;

    private function __construct(string $state)
    {
        $this->validate($state);

        $this->state = $state;
    }

    public static function fromString(string $state)
    {
        return new static($state);
    }

    public function get(): string
    {
        return $this->state;
    }

    public function is(string $state): bool
    {
        return $this->state === $state;
    }

    private function validate(string $state)
    {
        if(!in_array($state, [
            static::PENDING,
            static::COMMITTED,
            static::ABANDONED,
            static::REVIVED,
            static::CONFIRMED,
        ])) {
            throw new \InvalidArgumentException('Invalid state '. $state . ' passed.');
        };
    }
}
