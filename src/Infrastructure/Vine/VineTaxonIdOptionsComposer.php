<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Taxon\TaxonSelect\TaxonIdOptionsComposer;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;

class VineTaxonIdOptionsComposer implements TaxonIdOptionsComposer
{
    use HasLocale;

    private TaxonTreeRepository $taxonTreeRepository;

    private array $excludeTaxonIds = [];
    private TaxonomyRepository $taxonomyRepository;

    public function __construct(TaxonomyRepository $taxonomyRepository, TaxonTreeRepository $taxonTreeRepository)
    {
        $this->taxonTreeRepository = $taxonTreeRepository;
        $this->taxonomyRepository = $taxonomyRepository;
    }

    public function getTaxaAsOptions(string $taxonomyId): array
    {
        $result = [];

        $taxonNodes = $this->getFilteredTree($taxonomyId)->all();

        collect($taxonNodes)->each(function (TaxonNode $item) use (&$result) {
            $result[$item->getId()] = $item->getBreadCrumbLabel();
        });

        return $result;
        //        $grouped[$taxonomy->taxonomyId->get()] = ['label' => $taxonomy->getData('title.' . $this->getLocale()), 'options' => $options];
        //
        //         We remove the group key as we need to have non-assoc array for the multiselect options.
        //        return array_values($grouped);
    }

    public function getTaxaAsOptionsForMultiselect(string $taxonomyId): array
    {
        $options = $this->getTaxaAsOptions($taxonomyId);
        $values = [];

        foreach ($options as $id => $option) {
            $values[] = ['label' => $option, 'value' => $id];
        }

        return $values;
    }

    public function excludeTaxa(array|string $excludeTaxonIds): static
    {
        $this->excludeTaxonIds = (array)$excludeTaxonIds;

        return $this;
    }

    private function getFilteredTree(string $taxonomyId): TaxonTree
    {
        return $this->taxonTreeRepository->getTree()
            ->remove(function (TaxonNode $node) {
                return in_array($node->getId(), $this->excludeTaxonIds);
            })
            ->findMany(fn($node) => $node->getTaxonomyId() === $taxonomyId)
            ->sort('order');
    }

    //    private function composeLabels(array $taxa): array
    //    {
    //        return collect($taxa)->mapWithKeys(function (TaxonNode $taxon) {
    //            return [$taxon->getId() => $taxon->getBreadcrumbLabelWithoutRoot()];
    //        })->toArray();
    //    }

    //    public function get(array $taxonomyIds): array
    //    {
    //        $taxonomies = $this->taxonomyRepository->findMany($taxonomyIds);
    //        $taxaTree = $this->taxonTreeRepository->getTree();
    //
    //        /** @var Collection<Collection<Taxon>> $groupedByTaxonomy */
    //        $groupedByTaxonomy = collect($taxa)->groupBy(fn(Taxon $taxon) => $taxon->taxonomyId->get());
    //
    //        $result = [];
    //
    //        foreach ($groupedByTaxonomy as $taxonomyId => $taxaByTaxonomy) {
    //
    //            $_result = [];
    //
    //            /** @var Taxonomy $taxonomy */
    //            $taxonomy = collect($taxonomies)
    //                ->first(fn(Taxonomy $taxonomy) => $taxonomy->taxonomyId->get() === $taxonomyId);
    //
    //            foreach ($taxaByTaxonomy as $taxon) {
    //                $_result[] = [
    //                    'value' => $taxon->taxonId->get(),
    //                    'label' => $taxon->getData('title.' . $this->getLocale()->getLanguage()),
    //                ];
    //            }
    //
    //            $result[$taxonomyId] = [
    //                'label' => $taxonomy->getData('title.' . $this->getLocale()->getLanguage()),
    //                'options' => $_result,
    //            ];
    //        }
    //
    //        return $result;
    //    }
}
