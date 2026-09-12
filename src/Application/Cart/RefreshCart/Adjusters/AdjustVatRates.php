<?php

namespace Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjuster;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Application\VatRate\FindVatRateForOrder;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class AdjustVatRates implements Adjuster
{
    private VariantForCartRepository $variantForCartRepository;

    private FindVatRateForOrder $findVatRateForOrder;

    /** @var array<string, VariantForCart>|null */
    private ?array $variants = null;

    public function __construct(VariantForCartRepository $variantForCartRepository, FindVatRateForOrder $findVatRateForOrder)
    {
        $this->variantForCartRepository = $variantForCartRepository;
        $this->findVatRateForOrder = $findVatRateForOrder;
    }

    public function allowVatExemption(bool $allowVatExemption = true): static
    {
        $this->findVatRateForOrder->allowVatExemption($allowVatExemption);

        return $this;
    }

    public function disallowVatExemption(): static
    {
        return $this->allowVatExemption(false);
    }

    public function adjust(Order $order): void
    {
        $this->adjustLinePrices($order);

        // Allows to sequentially adjust the vat rates for the same order
        $this->findVatRateForOrder->clearMemoizedVatRates();
    }

    /** @param VariantForCart[] $variants */
    public function withVariants(array $variants): static
    {
        $adjuster = clone $this;
        $adjuster->variants = [];

        foreach ($variants as $variant) {
            $adjuster->variants[$variant->getVariantId()->get()] = $variant;
        }

        return $adjuster;
    }

    private function adjustLinePrices(Order $order): void
    {
        $variantLines = array_filter($order->getLines(), fn (Line $line) => $line->getPurchasableReference()?->isVariant());

        foreach ($variantLines as $line) {

            // Get variant of line for original vat percentage
            try {
                $variantId = $line->getPurchasableReference()->getId();
                $variant = $this->variants !== null
                    ? ($this->variants[$variantId] ?? null)
                    : $this->variantForCartRepository->findVariantForCart(VariantId::fromString($variantId));
            } catch (\Throwable $e) {
                // If variant is not found, skip vat adjustment for this line
                continue;
            }

            if (! $variant) {
                continue;
            }

            $originalVatPercentage = $variant->getSalePrice()->getVatPercentage();

            $vatPercentage = $this->findVatRateForOrder->findForLine($order, $originalVatPercentage);
            $linePrice = $line->getUnitPrice();

            if (! $linePrice->getVatPercentage()->equals($vatPercentage)) {
                $linePrice = $linePrice->changeVatPercentage($vatPercentage);
                $order->updateLinePrice($line->lineId, $linePrice);
            }
        }
    }
}
