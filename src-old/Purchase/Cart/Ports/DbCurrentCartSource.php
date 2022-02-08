<?php declare(strict_types=1);

namespace Purchase\Cart\Ports;

use Purchase\Cart\Domain\Cart;
use Purchase\Cart\Domain\CartReference;
use Purchase\Cart\Domain\CartRepository;
use Purchase\Cart\Domain\CurrentCartSource;
use Purchase\Cart\Domain\CartReferenceSource;
use Thinktomorrow\Trader\Purchase\Cart\Ports\CartState;
use Thinktomorrow\Trader\Purchase\Cart\Ports\CorruptCartModel;
use Thinktomorrow\Trader\Purchase\Cart\Ports\CartModelNotFound;
use function Thinktomorrow\Trader\Purchase\Cart\Ports\app;

class DbCurrentCartSource implements CurrentCartSource
{
    /** @var CartRepository */
    private $cartRepository;

    /** @var CartReferenceSource */
    private $cartReferenceSource;

    /** @var CartReference */
    private $cartReference;

    /** @var Cart */
    private $cart;

    public function __construct(CartRepository $cartRepository, CartReferenceSource $cartReferenceSource)
    {
        $this->cartRepository = $cartRepository;
        $this->cartReferenceSource = $cartReferenceSource;
    }

    public function get(): Cart
    {
        if ($this->cart) {
            return $this->cart;
        }

        return $this->cart = $this->fetch();
    }

    private function fetch(): Cart
    {
        try {
            if ($this->cartReference) {
                return $this->cartRepository->findByReference($this->cartReference);
            }
        } catch (CorruptCartModel | CartModelNotFound $e) {
            //
        }

        // New cart so make a new reference
        $this->setReference($this->cartRepository->nextReference());

        return $this->cartRepository->emptyCart($this->cartReference);
    }

    public function getReference(): ?CartReference
    {
        return $this->cartReference;
    }

    public function setReference(CartReference $cartReference): CurrentCartSource
    {
        $this->cartReference = $cartReference;

        return $this;
    }

    public function set(Cart $cart): CurrentCartSource
    {
        /** In case the cart is abandoned, we consider it to be revived after a change to the cart has occurred */
        if ($cart->state()->is(CartState::ABANDONED)) {
            app(ReviveCart::class)->handle($cart->reference());
        }

        /** Save version of cart in database */
        $this->cartRepository->save($cart);

        /** Store the cart reference in a cookie */
        $this->cartReferenceSource->set($cart->reference()->get());

        $this->clearCachedCart();

        return $this;
    }

    private function clearCachedCart()
    {
        $this->cart = null;
    }

    public function exists(): bool
    {
        return ($this->cartReference && $this->cartRepository->existsByReference($this->cartReference));
    }

    public function forget(): void
    {
        $this->clearCachedCart();

        // TODO: delete cart
    }
}
