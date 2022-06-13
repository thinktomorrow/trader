<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\OptionLinks;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class OptionLinksComposer
{
    private ProductRepository $productRepository;
    private ContainerInterface $container;

    public function __construct(ProductRepository $productRepository, ContainerInterface $container)
    {
        $this->productRepository = $productRepository;
        $this->container = $container;
    }

    /**
     * Compose all possible option combinations relative to the passed product variant. This is the action
     * used to determine the links behind each option on the product page, so we include an url as well.
     */
    public function get(ProductId $productId, VariantId $variantId, Locale $locale): OptionLinks
    {
        $product = $this->productRepository->find($productId);
        $variant = $product->findVariant($variantId);

        $variantOptions = [];
        foreach ($product->getOptions() as $option) {
            foreach ($option->getOptionValues() as $optionValue) {
                if (in_array($optionValue->optionValueId, $variant->getOptionValueIds())) {
                    $variantOptions[] = $optionValue;
                }
            }
        }

        $results = OptionLinks::empty();

        foreach ($product->getOptions() as $option) {
            foreach ($option->getOptionValues() as $optionValue) {

                // Merge this one with the current variant options
                $mergedVariantOptions = $this->addtoVariantOptions($variantOptions, $optionValue);

                // Create the option link - Find a variant for this combination?
                $optionLink = $this->container->get(OptionLink::class)::from(
                    $option,
                    $optionValue,
                    $this->findVariantByOptionValues($product, $mergedVariantOptions)
                );

                $optionLink->setLocale($locale);

                // If this option value also belongs to this current variant, we'll mark it as active
                if (in_array($optionValue->optionValueId, $variant->getOptionValueIds())) {
                    $optionLink->markActive();
                }

                $results = $results->add($optionLink);
            }
        }

        return $results;
    }

    /**
     * Merges a new option value into this set of variant options. Specifically for variant options it is
     * important that each option is only represented once. So a new option value will always replace
     * an option value with the same option reference.
     */
    private function addtoVariantOptions(array $variantOptions, \Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue $optionValue): array
    {
        $result = [];

        foreach ($variantOptions as $variantOption) {
            $result[] = ($variantOption->optionId->equals($optionValue->optionId))
                ? $optionValue
                : $variantOption;
        }

        return $result;
    }

    private function findVariantByOptionValues(\Thinktomorrow\Trader\Domain\Model\Product\Product $product, array $mergedVariantOptions): ?\Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant
    {
        $mergedVariantOptionValueIds = array_map(fn (\Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue $optionValue) => $optionValue->optionValueId, $mergedVariantOptions);

        foreach ($product->getVariants() as $variant) {
            if ($this->hasExactOptionsMatch($variant->getOptionValueIds(), $mergedVariantOptionValueIds)) {
                return $variant;
            }
        }

        return null;
    }

    private function hasExactOptionsMatch(array $firstOptionValueIds, array $secondOptionValueIds): bool
    {
        // array_diff with empty array returns unexpected results
        if (count($firstOptionValueIds) < 1 || count($secondOptionValueIds) < 1) {
            return false;
        }

        return count(array_diff($firstOptionValueIds, $secondOptionValueIds)) == 0;
    }
}
