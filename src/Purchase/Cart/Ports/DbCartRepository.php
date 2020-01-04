<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Cart\Ports;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Cart;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CartReference;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CartRepository;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Exceptions\CartModelNotFound;

class DbCartRepository implements CartRepository
{
    /** @var CartFactory */
    private $cartFactory;

    public function __construct(CartFactory $cartFactory)
    {
        $this->cartFactory = $cartFactory;

        $this->model = new CartModel();
    }

    public function existsByReference(CartReference $cartReference): bool
    {
        return (0 < $this->model->where('reference', $cartReference->get())->count());
    }

    /**
     * @param CartReference $cartReference
     * @return Cart
     * @throws CartModelNotFound
     * @throws CorruptCartModel
     */
    public function findByReference(CartReference $cartReference): Cart
    {
        if (!$model = $this->model->where('reference', $cartReference->get())->first()) {
            throw new CartModelNotFound('No cart model found in db storage by reference ' . $cartReference->get());
        }

        $cart = $this->composeCart($cartReference, $model);
        $this->reset();

        return $cart;
    }

    public function get(): Collection
    {
        $carts = $this->model->get()->map(function($model){
            return $this->composeCart(CartReference::fromString($model->reference), $model);
        });

        $this->reset();

        return $carts;
    }

    private function reset()
    {
        $this->model = new CartModel();
    }

    public function save(Cart $cart): void
    {
        $data = json_encode($cart->toArray());

        $model = CartModel::findByReference($cart->reference());

        if ($model) {
            $model->data = $data;
            $model->save();

            return;
        }

        CartModel::create([
            'reference' => $cart->reference()->get(),
            'data'      => $data,
        ]);
    }

    public function filterByState(string ...$state): CartRepository
    {
        $this->model = $this->model->whereIn('state', $state);

        return $this;
    }

    public function lastUpdatedBefore(\DateTime $threshold): CartRepository
    {
        $this->model = $this->model->where('updated_at', '<', $threshold);

        return $this;
    }

    public function nextReference(): CartReference
    {
        return CartReference::fromString((string)$this->generateOrderReference());
    }

    private function composeCart(CartReference $cartReference, CartModel $model)
    {
        $data = json_decode($model->data, true);

        $data['state'] = $model->state;

        if (!CartModel::validateDataIntegrity($data)) {
            throw new CorruptCartModel('Cart model for reference [' . $cartReference->get() . '] has missing or invalid fields stored in database.');
        }

        $cart = $this->stale || !$this->refreshAllowed($model->state)
            ? $this->cartFactory->create($cartReference, $data)
            : $this->cartFactory->createFresh($cartReference, $data);

        return $cart;
    }

    private function generateOrderReference(): string
    {
        $order_reference = $this->createRandomString();

        while(CartModel::findByReference(CartReference::fromString($order_reference))){
            $order_reference = $this->createRandomString();
        }

        return $order_reference;
    }

    private function createRandomString()
    {
        return config('optiphar.order-ref-prefix') . time() . '-' .str_pad( (string) mt_rand(1,999),3,"0",STR_PAD_LEFT);
    }

    private function refreshAllowed($cartState = null): bool
    {
        if(!$cartState) return true;

        return in_array($cartState, [
            CartState::PENDING,
            CartState::COMMITTED,
            CartState::ABANDONED,
            CartState::REVIVED,
        ]);
    }
}
