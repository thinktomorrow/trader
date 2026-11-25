<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Application\Cart\PaymentMethod\PaymentMethodForCart;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\ShippingProfileForCart;

final class FindCartPromoIncentives
{
    public function __construct(private FindPromoSuggestionsForProduct $suggestions) {}

    /**
     * @param iterable<ShippingProfileForCart> $shippingProfiles
     * @param iterable<PaymentMethodForCart> $paymentMethods
     * @return Collection<int, PromoSuggestion>
     */
    public function get(Cart $cart, string $locale, iterable $shippingProfiles = [], iterable $paymentMethods = []): Collection
    {
        $incentives = collect();

        foreach ($cart->getLines() as $line) {
            $reference = $line->getPurchasableReference();

            if (! $reference->isVariant()) {
                continue;
            }

            $incentives = $incentives->concat($this->suggestions->cartIncentivesFor(
                PromoPreviewProduct::fromCartLine($line),
                $locale,
                $cart,
            ));
        }

        foreach ($shippingProfiles as $shippingProfile) {
            $incentives = $incentives->concat($this->suggestions->serviceIncentivesFor($shippingProfile, $locale, $cart));
        }

        foreach ($paymentMethods as $paymentMethod) {
            $incentives = $incentives->concat($this->suggestions->serviceIncentivesFor($paymentMethod, $locale, $cart));
        }

        return $this->withoutAppliedPromos($incentives, $cart);
    }

    /** @return Collection<int, PromoSuggestion> */
    public function forShippingProfile(Cart $cart, ShippingProfileForCart $shippingProfile, string $locale): Collection
    {
        return $this->withoutAppliedPromos($this->suggestions->serviceIncentivesFor($shippingProfile, $locale, $cart), $cart);
    }

    /** @return Collection<int, PromoSuggestion> */
    public function forPaymentMethod(Cart $cart, PaymentMethodForCart $paymentMethod, string $locale): Collection
    {
        return $this->withoutAppliedPromos($this->suggestions->serviceIncentivesFor($paymentMethod, $locale, $cart), $cart);
    }

    /**
     * @param Collection<int, PromoSuggestion> $incentives
     * @return Collection<int, PromoSuggestion>
     */
    private function withoutAppliedPromos(Collection $incentives, Cart $cart): Collection
    {
        $appliedPromoIds = collect($cart->getAllDiscounts())
            ->map(fn ($discount): ?string => $discount->getData('promo_id'))
            ->filter()
            ->all();

        return $incentives
            ->reject(fn (PromoSuggestion $suggestion): bool => in_array($suggestion->promoId, $appliedPromoIds, true))
            ->unique(fn (PromoSuggestion $suggestion): string => $suggestion->promoId)
            ->values();
    }
}
