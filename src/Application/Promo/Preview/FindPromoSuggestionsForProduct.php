<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;

final class FindPromoSuggestionsForProduct
{
    /** @var list<PromoPreviewEligibility> */
    private array $eligibilities;

    /** @param iterable<PromoPreviewEligibility> $eligibilities */
    public function __construct(private PromoRepository $promoRepository, iterable $eligibilities = [])
    {
        $this->eligibilities = is_array($eligibilities) ? array_values($eligibilities) : iterator_to_array($eligibilities, false);
    }

    public function get(ProductDetail $product, string $locale, int $quantity = 1, ?Cart $cart = null): PromoSuggestions
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Preview quantity must be at least one.');
        }

        if (! $product->isAvailable()) {
            return new PromoSuggestions(collect(), collect());
        }

        $candidates = $this->candidates(PromoPreviewProduct::fromProductDetail($product), $locale, $quantity, $cart);

        return new PromoSuggestions(
            $candidates->filter(fn (array $candidate): bool => $candidate['status'] === PromoPreviewStatus::Applicable
                && (bool) $candidate['promo']->getData('show_on_pdp_when_ready', false))->pluck('suggestion')->values(),
            $candidates->filter(fn (array $candidate): bool => $candidate['status'] === PromoPreviewStatus::Conditional
                && (bool) $candidate['promo']->getData('show_on_pdp_when_pending', false))->pluck('suggestion')->values(),
        );
    }

    /** @return Collection<int, PromoSuggestion> */
    public function cartIncentivesForProduct(PromoPreviewProduct $product, string $locale, Cart $cart): Collection
    {
        return $this->candidates($product, $locale, 0, $cart)
            ->filter(fn (array $candidate): bool => (bool) $candidate['promo']->getData('show_on_cart', false))
            ->pluck('suggestion')
            ->unique(fn (PromoSuggestion $suggestion): string => $suggestion->promoId)
            ->values();
    }

    /** @return Collection<int, array{promo: Promo, status: PromoPreviewStatus, suggestion: PromoSuggestion}> */
    private function candidates(PromoPreviewProduct $product, string $locale, int $quantity, ?Cart $cart): Collection
    {
        $candidates = collect();

        foreach ($this->promoRepository->getAvailablePromos() as $promo) {
            if (! $promo->getData('show_on_pdp_when_ready', false)
                && ! $promo->getData('show_on_pdp_when_pending', false)
                && ! $promo->getData('show_on_cart', false)) {
                continue;
            }

            foreach ($promo->getDiscounts() as $discount) {
                $eligibility = $this->eligibilityFor($discount::getMapKey());

                if (! $eligibility || ($status = $eligibility->assess($promo, $discount, $product, $quantity, $cart)) === PromoPreviewStatus::Irrelevant) {
                    continue;
                }

                if ($promo->hasCouponCode() && $cart?->getEnteredCoupon() !== $promo->getCouponCode()) {
                    $status = PromoPreviewStatus::Conditional;
                }

                $candidates->push([
                    'promo' => $promo,
                    'status' => $status,
                    'suggestion' => PromoSuggestion::fromPromo($promo, $discount, $locale),
                ]);
            }
        }

        return $candidates;
    }

    private function eligibilityFor(string $key): ?PromoPreviewEligibility
    {
        foreach ($this->eligibilities as $eligibility) {
            if ($eligibility->supports($key)) {
                return $eligibility;
            }
        }

        return null;
    }
}
