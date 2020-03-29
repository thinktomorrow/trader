<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Cart\Ports;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Cart;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CartFactory;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CartReference;
use Thinktomorrow\Trader\Purchase\Cart\Domain\CartRepository;
use Thinktomorrow\Trader\Common\Domain\References\ReferenceValue;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Exceptions\CorruptCartModel;
use Thinktomorrow\Trader\Purchase\Cart\Domain\Exceptions\CartModelNotFound;

class DbCartRepository implements CartRepository
{
    /** @var CartFactory */
    private $cartFactory;

    /** @var ReferenceValue */
    private $referenceValue;

    public function __construct(CartFactory $cartFactory, ReferenceValue $referenceValue)
    {
        $this->cartFactory = $cartFactory;
        $this->referenceValue = $referenceValue;

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
        $cartReference = $this->referenceValue->generate()->get();

        while(CartModel::findByReference(CartReference::fromString($cartReference))){
            $cartReference = $this->referenceValue->generate()->get();
        }

        return CartReference::fromString($cartReference);
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

    public function emptyCart(CartReference $cartReference): Cart
    {
        return $this->cartFactory->createEmpty($cartReference);
    }
}
