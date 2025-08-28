<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Product\VariantProperties;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\VariantProperty;
use Thinktomorrow\Trader\Domain\Model\Taxon\Taxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;

/**
 * DTO for composing the simple option array,
 * ready for usage in an admin form select
 */
class VariantPropertiesForSelect
{
    use HasLocale;

    private ProductRepository $productRepository;
    private TaxonRepository $taxonRepository;
    private TaxonomyRepository $taxonomyRepository;

    public function __construct(ProductRepository $productRepository, TaxonRepository $taxonRepository, TaxonomyRepository $taxonomyRepository)
    {
        $this->productRepository = $productRepository;
        $this->taxonRepository = $taxonRepository;
        $this->taxonomyRepository = $taxonomyRepository;
    }

    public function get(string $productId): array
    {
        $product = $this->productRepository->find(ProductId::fromString($productId));
        $taxonIds = array_map(fn (VariantProperty $prop) => $prop->taxonId->get(), $product->getVariantProperties());
        $taxa = $this->taxonRepository->findMany($taxonIds);
        $taxonomies = $this->taxonomyRepository->findManyByTaxa($taxonIds);

        /** @var Collection<Collection<Taxon>> $groupedByTaxonomy */
        $groupedByTaxonomy = collect($taxa)->groupBy(fn (Taxon $taxon) => $taxon->taxonomyId->get());

        $result = [];

        foreach ($groupedByTaxonomy as $taxonomyId => $taxaByTaxonomy) {

            $_result = [];

            /** @var Taxonomy $taxonomy */
            $taxonomy = collect($taxonomies)
                ->first(fn (Taxonomy $taxonomy) => $taxonomy->taxonomyId->get() === $taxonomyId);

            foreach ($taxaByTaxonomy as $taxon) {
                $_result[] = [
                    'value' => $taxon->taxonId->get(),
                    'label' => $taxon->getData('title.' . $this->getLocale()->getLanguage()),
                ];
            }

            $result[$taxonomyId] = [
                'label' => $taxonomy->getData('title.' . $this->getLocale()->getLanguage()),
                'options' => $_result,
            ];
        }

        return $result;
    }
}
