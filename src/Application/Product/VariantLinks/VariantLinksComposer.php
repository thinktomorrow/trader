<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\VariantLinks;

use Psr\Container\ContainerInterface;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Application\Product\Taxa\VariantTaxonItem;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class VariantLinksComposer
{
    private ProductRepository $productRepository;
    private ProductDetailRepository $productDetailRepository;
    private ContainerInterface $container;

    public function __construct(ProductRepository $productRepository, ProductDetailRepository $productDetailRepository, ContainerInterface $container)
    {
        $this->productRepository = $productRepository;
        $this->productDetailRepository = $productDetailRepository;
        $this->container = $container;
    }

    /**
     * Compose all possible option combinations relative to the passed product variant. This is the action
     * used to determine the links behind each option on the product page, so we include a url as well.
     */
    public function get(ProductId $productId, VariantId $variantId, Locale $locale): VariantLinks
    {
        $productDetail = $this->productDetailRepository->findProductDetail($variantId);
        $product = $this->productRepository->find($productId);

        /**
         * When there are no variant properties set on the product, but there are
         * multiple variants, the variants themselves are used as links instead.
         * Here we use the option_title of the variant if present
         */
        if (count($productDetail->getProductVariantProperties()) < 1) {
            return $this->composeLinksForEachVariant($product, $locale, $variantId);
        }

        return $this->composeLinksForEachVariantProperty($productDetail, $product, $locale);
    }

    private function composeLinksForEachVariant(Product $product, Locale $locale, VariantId $variantId): VariantLinks
    {
        $results = VariantLinks::empty();

        foreach ($product->getVariants() as $variant) {
            $variantLink = $this->container->get(VariantLink::class)::fromVariant($variant);
            $variantLink->setLocale($locale);

            if ($variant->variantId->equals($variantId)) {
                $variantLink->markActive();
            }

            $results = $results->add($variantLink);
        }

        return $results;
    }

    private function composeLinksForEachVariantProperty(ProductDetail $productDetail, Product $product, Locale $locale): VariantLinks
    {
        $results = VariantLinks::empty();

        $currentVariantProperties = $productDetail->getVariantProperties();

        // Merge all other variant properties with the current variant properties to create all possible combinations
        foreach ($productDetail->getProductVariantProperties() as $prop) {

            $mergedVariantProperties = $this->mergeWithVariantProperties($currentVariantProperties, $prop);

            // Create the option link - Find a variant for this combination?
            $variantLink = $this->container->get(VariantLink::class)::fromVariantProperty(
                $prop,
                $this->findVariantByProperties($product, $mergedVariantProperties)
            );

            $variantLink->setLocale($locale);

            // If this option value also belongs to this current variant, we'll mark it as active
            if (in_array($prop->getTaxonId(), array_map(fn (ProductTaxonItem|VariantTaxonItem $prop) => $prop->getTaxonId(), $currentVariantProperties))) {
                $variantLink->markActive();
            }

            $results = $results->add($variantLink);
        }

        return $results;
    }

    /**
     * Merges a new option value into this set of variant options. Specifically for variant options it is
     * important that each option is only represented once. So a new option value will always replace
     * an option value with the same option reference.
     */
    private function mergeWithVariantProperties(array $variantProperties, ProductTaxonItem $prop): array
    {
        $result = [];

        foreach ($variantProperties as $variantProp) {
            $result[] = ($variantProp->getTaxonomyId() === $prop->getTaxonomyId())
                ? $prop
                : $variantProp;
        }

        return $result;
    }

    private function findVariantByProperties(Product $product, array $variantProperties): ?\Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant
    {
        $taxonIds = array_map(fn (ProductTaxonItem|VariantTaxonItem $prop) => $prop->getTaxonId(), $variantProperties);

        foreach ($product->getVariants() as $variant) {
            if ($this->hasExactVariantPropertiesMatch($variant, $taxonIds)) {
                return $variant;
            }
        }

        return null;
    }

    private function hasExactVariantPropertiesMatch(Variant $variant, array $taxonIds): bool
    {
        $variantTaxonIds = array_map(fn ($prop) => $prop->taxonId->get(), $variant->getVariantProperties());

        // array_diff with empty array returns unexpected results
        if (count($variantTaxonIds) < 1 || count($taxonIds) < 1) {
            return false;
        }

        return count(array_diff($variantTaxonIds, $taxonIds)) == 0;
    }
}
