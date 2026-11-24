<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ApplicableDiscountFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;

final class FindPromoSuggestionsForProduct
{
    public function __construct(private PromoRepository $promoRepository, private ApplicableDiscountFactory $discountFactory) {}

    public function get(ProductDetail $product, string $locale, int $quantity = 1, ?Cart $cart = null): PromoSuggestions
    {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Preview quantity must be at least one.');
        }

        if (! $product->isAvailable()) {
            return new PromoSuggestions(collect(), collect());
        }

        $candidates = $this->candidates(PromoPreviewProduct::fromProductDetail($product, $quantity), $locale, $cart);

        return new PromoSuggestions(
            $candidates->filter(fn (array $candidate): bool => $candidate['status'] === PromoPreviewStatus::Applicable
                && (bool) $candidate['promo']->getData('show_on_pdp', false))->pluck('suggestion')->values(),
            $candidates->filter(fn (array $candidate): bool => $candidate['status'] === PromoPreviewStatus::Conditional
                && (bool) $candidate['promo']->getData('show_on_pdp', false))->pluck('suggestion')->values(),
        );
    }

    /** @return Collection<int, PromoSuggestion> */
    public function cartIncentivesFor(PromoPreviewProduct|Cart|CartShipping|CartPayment $subject, string $locale, Cart $cart): Collection
    {
        return $this->candidates($subject, $locale, $cart)
            ->filter(fn (array $candidate): bool => (bool) $candidate['promo']->getData('show_on_cart', false))
            ->pluck('suggestion')
            ->unique(fn (PromoSuggestion $suggestion): string => $suggestion->promoId)
            ->values();
    }

    /** @return Collection<int, array{promo: Promo, status: PromoPreviewStatus, suggestion: PromoSuggestion}> */
    private function candidates(PromoPreviewProduct|Cart|CartShipping|CartPayment $subject, string $locale, ?Cart $cart): Collection
    {
        $candidates = collect();

        foreach ($this->promoRepository->getAvailablePromos() as $promo) {
            if (! $promo->getData('show_on_pdp', false) && ! $promo->getData('show_on_cart', false)) {
                continue;
            }

            foreach ($promo->getDiscounts() as $discount) {
                if (! $this->discountFactory->supports($discount::getMapKey())) {
                    continue;
                }

                $status = $this->discountFactory->forDiscount($promo, $discount)->promoPreviewStatus($subject, $cart);

                if ($status === PromoPreviewStatus::Irrelevant) {
                    continue;
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
}
