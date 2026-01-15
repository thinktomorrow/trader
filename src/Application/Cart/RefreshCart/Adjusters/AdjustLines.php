<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;

class AdjustLines implements Adjuster
{
    private VariantForCartRepository $variantForCartRepository;
    private AdjustLine $adjustLine;

    public function __construct(VariantForCartRepository $variantForCartRepository, AdjustLine $adjustLine)
    {
        $this->variantForCartRepository = $variantForCartRepository;
        $this->adjustLine = $adjustLine;
    }

    public function adjust(Order $order): void
    {
        // Extract all the variants in the cart
        $variantLines = array_filter($order->getLines(), fn(Line $line) => $line->getPurchasableReference()->isVariant());
        $variantIds = array_map(fn(Line $line) => $line->getPurchasableReference()->getId(), $variantLines);
        $variants = $this->variantForCartRepository->findAllVariantsForCart($variantIds);

        foreach ($order->getLines() as $line) {
            // No longer there? Maybe deleted.
            if (!$variant = $this->findVariant($variants, VariantId::fromString($line->getPurchasableReference()->getId()))) {
                $order->deleteLine($line->lineId);

                // TODO: event + note
                continue;
            }

            // Variant can be no longer available due to stock or whatever...
            if (!in_array($variant->getState(), VariantState::availableStates())) {
                $order->deleteLine($line->lineId);

                continue;
            }

            // Price can be changed in the meanwhile
            if (!$line->getUnitPrice()->getExcludingVat()->equals($variant->getUnitPrice()->getExcludingVat())) {
                $line->updatePrice($variant->getUnitPrice());
            }

            // Update sale price reference - so the system sale price discount can be recalculated
            $line->addData([
                'unit_price_excl' => $variant->getUnitPrice()->getExcludingVat()->getAmount(),
                'unit_price_incl' => $variant->getUnitPrice()->getIncludingVat()->getAmount(),
                'sale_price_excl' => $variant->getSalePrice()?->getExcludingVat()?->getAmount(),
                'sale_price_incl' => $variant->getSalePrice()?->getIncludingVat()?->getAmount(),
            ]);

            $this->adjustLine->adjust($order, $line);
        }

        // todo: Fetch variantForCart...
        // Update lines for: title, discounts, ...
    }

    private function findVariant(array $variants, VariantId $variantId): ?VariantForCart
    {
        foreach ($variants as $variant) {
            if ($variant->getVariantId()->equals($variantId)) {
                return $variant;
            }
        }

        return null;
    }
}
