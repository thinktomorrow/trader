<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Common\HasLocale;
use Thinktomorrow\Trader\Application\Taxon\Queries\TaxaSelectOptions;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyRepository;

class VineTaxaSelectOptions implements TaxaSelectOptions
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

    public function getByTaxonomy(string $taxonomyId): array
    {
        $result = [];

        $taxonNodes = $this->getFilteredTree($taxonomyId)->all();

        collect($taxonNodes)->each(function (TaxonNode $item) use (&$result) {
            $result[$item->getId()] = $item->getBreadCrumbLabel();
        });

        return $result;
    }

    public function getForMultiselectByTaxonomy(string $taxonomyId): array
    {
        $options = $this->getByTaxonomy($taxonomyId);
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
            ->findMany(fn ($node) => $node->getTaxonomyId() === $taxonomyId)
            ->sort('order');
    }
}
