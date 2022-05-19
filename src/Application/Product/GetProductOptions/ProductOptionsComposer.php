<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\GetProductOptions;

use Assert\Assertion;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;

class ProductOptionsComposer
{
    private ProductOptionsRepository $productOptionsRepository;
    private VariantProductOptionsRepository $variantRepository;

    public function __construct(ProductOptionsRepository $productOptionsRepository, VariantProductOptionsRepository $variantRepository)
    {
        $this->productOptionsRepository = $productOptionsRepository;
        $this->variantRepository = $variantRepository;
    }

    /**
     * Compose all possible option combinations relative to the passed product variant. This is the action
     * used to determine the links behind each option on the product page, so we include an url as well.
     */
    public function get(ProductId $productId, VariantId $variantId, Locale $locale): ProductOptions
    {
        // Get all options of the product
        $productOptions = $this->productOptionsRepository->getProductOptions($productId);

        if($productOptions->isEmpty()) return $productOptions;

        // Get all product variants
        $variants = $this->variantRepository->getVariantProductOptions($productId); // Idea: perhaps pass the productOptions

        if($variants->isEmpty()) return ProductOptions::fromType([]);

        // Current set of productOptions
        $variant = $variants->find($variantId);

        /**
         * Loop over each productOptions as an alternation of this current set
         * and attach their url if they match with exactly a set of productOptions.
         *
         * @var ProductOption $productOption
         */
        foreach($productOptions as $productOption) {

            // merge the product option with the current variant options. The merge will make
            // sure we don't have more than one option value per option.
            $variantOptions = $variant->getOptions()->merge($productOption);

            // Find a variant for this combination?
            if($match = $variants->findByOptionValues($variantOptions))
            {
                $productOption->setUrl($match->getUrl($locale));
            }

            // If this option value also belongs to this current variant, we'll mark it as active
            if($variant->hasOptionValueId($productOption->optionValueId)) {
                $productOption->markActive();
            }
        }

        return $productOptions;
//        Assertion::allIsInstanceOf($otherProducts, ProductOptions::class);



//        // If present product does not have an option value for the targeted option, we cannot provide a product
//        if (! in_array($option->getId(), $targetOptionValueIds)) {
//            return null;
//        }

        // 'color' => [
        //                'label' => 'color',
        //                'value' => 'blauw',
        //            ],

        // All options of this product
    }

//    public function find($productDetail, array $productDetails, Option $option): ?VariantForProductOption
//    {
//        $desiredOptionIds = [];
//
//        foreach ($currentProduct->getOptions() as $currentOption) {
//            $desiredOptionIds[] = ($option->getOptionTypeId() === $currentOption->getOptionTypeId())
//                ? $option->getId()
//                : $currentOption->getId();
//        }
//
//        // If present product does not have an option value for the targeted option, we cannot provide a product
//        if (! in_array($option->getId(), $desiredOptionIds)) {
//            return null;
//        }
//
//        // productdetails
//
//        return $productGroup->getProducts()->first(function (VariantForProductOption $product) use ($desiredOptionIds) {
//            $fullMatch = true;
//
//            foreach ($desiredOptionIds as $optionId) {
//                if (! $product->hasOption($optionId)) {
//                    $fullMatch = false;
//                }
//            }
//
//            return $fullMatch;
//        });
//    }
}
