<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
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
        $variantIds = array_map(fn (Line $line) => $line->getVariantId(), $order->getLines());
        $variants = $this->variantForCartRepository->findAllVariantsForCart($variantIds);

        foreach ($order->getLines() as $line) {
            // No longer there? Maybe deleted.
            if (! $variant = $this->findVariant($variants, $line->getVariantId())) {
                $order->deleteLine($line->lineId);

                // TODO: event + note
                continue;
            }

            // Variant can be no longer available due to stock or whatever...
            if (! in_array($variant->getState(), VariantState::availableStates())) {
                $order->deleteLine($line->lineId);

                continue;
            }

            // Price can be changed in the meanwhile
            if (! $line->getLinePrice()->getExcludingVat()->equals($variant->getSalePrice()->getExcludingVat())) {
                $line->updatePrice(LinePrice::fromPrice($variant->getSalePrice()));
            }

            // AdjustTax

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
