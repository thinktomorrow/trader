<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Cart\Ports;

use Thinktomorrow\Trader\Cart\Domain\CartRepository;
use Thinktomorrow\Trader\Order\Domain\Exceptions\CorruptOrderModel;
use Thinktomorrow\Trader\Order\Domain\Exceptions\OrderModelNotFound;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Order\Domain\OrderReference;
use Thinktomorrow\Trader\Order\Domain\OrderReferenceSource;

class CurrentCart
{
    private CartRepository $cartRepository;
    private OrderReferenceSource $orderReferenceSource;
    private ?OrderReference $orderReference = null;
    private ?Order $cart = null;

    public function __construct(CartRepository $cartRepository, OrderReferenceSource $orderReferenceSource)
    {
        $this->cartRepository = $cartRepository;
        $this->orderReferenceSource = $orderReferenceSource;
    }

    public function exists(): bool
    {
        return ($this->orderReference && $this->cartRepository->existsByReference($this->orderReference));
    }

    public function get(): Order
    {
        if ($this->cart) {
            return $this->cart;
        }

        return $this->cart = $this->fetch();
    }

    private function fetch(): Order
    {
        try {
            if ($this->orderReference) {
                return $this->cartRepository->findByReference($this->orderReference);
            }
        } catch (CorruptOrderModel | OrderModelNotFound $e) {
            //
        }

        // New cart so make a new reference
        $this->setReference($this->cartRepository->nextReference());

        return $this->cartRepository->emptyCart($this->orderReference);
    }

    public function getReference(): ?OrderReference
    {
        return $this->orderReference;
    }

    public function setReference(OrderReference $orderReference): void
    {
        $this->orderReference = $orderReference;
//        $this->cart = null;
    }

//    public function set(Order $cart): CurrentCartSource
//    {
//        /** In case the cart is abandoned, we consider it to be revived after a change to the cart has occurred */
//        if ($cart->state()->is(CartState::ABANDONED)) {
//            app(ReviveCart::class)->handle($cart->reference());
//        }
//
//        /** Save version of cart in database */
//        $this->cartRepository->save($cart);
//
//        /** Store the cart reference in a cookie */
//        $this->orderReferenceSource->set($cart->reference()->get());
//
//        $this->clearCachedCart();
//
//        return $this;
//    }

//    private function clearCachedCart()
//    {
//        $this->cart = null;
//    }

//    public function forget(): void
//    {
//        $this->cart = null;
//    }
}
