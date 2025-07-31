<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Taxon\TaxonSelect\TaxonIdOptionsComposer;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;

class VineTaxonIdOptionsComposer implements TaxonIdOptionsComposer
{
    private TaxonTreeRepository $taxonTreeRepository;

    private array $excludeTaxonIds = [];

    public function __construct(TaxonTreeRepository $taxonTreeRepository)
    {
        $this->taxonTreeRepository = $taxonTreeRepository;
    }

    public function getTaxaAsOptions(string $taxonomyId): array
    {
        $grouped = [];

        collect($this->getFlattened($taxonomyId))->each(function ($item) use (&$grouped) {
            // First item should be toplevel so it is selectable as well (since group labels aren't selectable)
            $values = array_merge($this->composeLabels([$item['root']]), $item['values']);
            $grouped[$item['root']->getKey()] = ['label' => $item['root']->getLabel(), 'options' => $values];
        });

        // We remove the group key as we need to have non-assoc array for the multiselect options.
        return array_values($grouped);
    }

    public function getTaxaAsOptionsForMultiselect(string $taxonomyId): array
    {
        $options = $this->getTaxaAsOptions($taxonomyId);

        return array_map(function ($group) {
            foreach ($group['options'] as $id => $value) {
                $values[] = ['label' => $value, 'value' => $id];
            }

            $group['options'] = $values;

            return $group;
        }, $options);
    }

    public function excludeTaxa(array|string $excludeTaxonIds): static
    {
        $this->excludeTaxonIds = (array)$excludeTaxonIds;

        return $this;
    }

    private function getFlattened(string $taxonomyId): array
    {
        $rootTaxa = $this->getFilteredTree($taxonomyId)->all();

        $taxaPerRoot = [];

        foreach ($rootTaxa as $rootTaxon) {
            $taxaPerRoot[] = [
                'root' => $rootTaxon,
                'values' => $this->composeLabels(
                    array_merge([$rootTaxon], $rootTaxon->getChildNodes()->flatten()->all()),
                ),
            ];
        }

        return $taxaPerRoot;
    }

    private function getFilteredTree(string $taxonomyId): TaxonTree
    {
        return $this->taxonTreeRepository->getTree()
            ->remove(function (TaxonNode $node) use ($taxonomyId) {
                return $node->getTaxonomyId() !== $taxonomyId;
            })
            ->remove(function (TaxonNode $node) {
                return in_array($node->getId(), $this->excludeTaxonIds);
            });
    }

    private function composeLabels(array $taxa): array
    {
        return collect($taxa)->mapWithKeys(function (TaxonNode $taxon) {
            return [$taxon->getId() => $taxon->getBreadcrumbLabelWithoutRoot()];
        })->toArray();
    }
}
