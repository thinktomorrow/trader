<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Vine;

use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Application\Taxon\TaxonSelect\TaxonIdOptionsComposer;

class VineTaxonIdOptionsComposer implements TaxonIdOptionsComposer
{
    use UsesTaxonTree;

    private TaxonTreeRepository $taxonTreeRepository;

    private array $excludeTaxonIds = [];
    private array $includeTaxonRootIds = [];
    private bool $includeRoots = false;

    public function __construct(TaxonTreeRepository $taxonTreeRepository)
    {
        $this->taxonTreeRepository = $taxonTreeRepository;
    }

    public function getOptions(): array
    {
        $grouped = [];

        collect($this->getFlattened())->each(function ($item) use (&$grouped) {


            $grouped[$item['root']->getKey()] = ['group' => $item['root']->getLabel(), 'values' => $item['values']];

//            $values = array_values(array_map(fn ($value) => [ // reset keys with array_values for proper array for multiselect
//                'id' => array_search($value, $item['values']), // Get key (id)
//                'label' => $value,
//            ], $item['values']));
//            $grouped[$item['root']->getKey()] = ['group' => $item['root']->getLabel(), 'values' => $values];
        });

        // We remove the group key as we need to have non-assoc array for the multiselect options.
        return array_values($grouped);
    }

    public function exclude(array|string $excludeTaxonIds): static
    {
        $this->excludeTaxonIds = (array) $excludeTaxonIds;

        return $this;
    }

    public function include(array|string $includeTaxonRootIds): static
    {
        $this->includeTaxonRootIds = (array) $includeTaxonRootIds;

        return $this;
    }

    public function includeRoots(bool $includeRoots = true): static
    {
        $this->includeRoots = $includeRoots;

        return $this;
    }

    private function getFlattened(): array
    {
        $rootTaxa = $this->getFilteredTree()->all();

        $taxaPerRoot = [];

        foreach ($rootTaxa as $rootTaxon) {
            $taxaPerRoot[] = [
                'root' => $rootTaxon,
                'values' => $this->composeLabels(
                    $this->includeRoots
                        ? array_merge([$rootTaxon], $rootTaxon->getChildNodes()->flatten()->all())
                        : $rootTaxon->getChildNodes()->flatten()->all()
                ),
            ];
        }

        return $taxaPerRoot;
    }

    private function getFilteredTree(): TaxonTree
    {
        return $this->getTree()
            ->remove(function (TaxonNode $node) {
                if ($node->isRootNode() && ! empty($this->includeTaxonRootIds)) {
                    return ! in_array($node->getId(), $this->includeTaxonRootIds);
                }

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
