<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\Controllers;

use Illuminate\Http\Request;
use Thinktomorrow\Trader\Domain\Common\Cash\IntegerConverter;
use Thinktomorrow\Trader\Application\Product\Grid\GridRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Thinktomorrow\Trader\Infrastructure\Vine\ExtractActiveTaxonFilters;
use Thinktomorrow\Trader\Infrastructure\Vine\GetAvailableTaxonFilters;
use Thinktomorrow\Trader\Application\Taxon\Category\CategoryRepository;

class CategoryController
{
    private GridRepository $gridRepository;
    private CategoryRepository $categoryRepository;
    private GetAvailableTaxonFilters $getAvailableTaxonFilters;
    private ExtractActiveTaxonFilters $getActiveTaxonFilters;

    public function __construct(GridRepository $gridRepository, CategoryRepository $categoryRepository, GetAvailableTaxonFilters $getAvailableTaxonFilters, ExtractActiveTaxonFilters $getActiveTaxonFilters)
    {
        $this->gridRepository = $gridRepository;
        $this->categoryRepository = $categoryRepository;
        $this->getAvailableTaxonFilters = $getAvailableTaxonFilters;
        $this->getActiveTaxonFilters = $getActiveTaxonFilters;
    }

    public function show(string $taxonKeys, Request $request)
    {
        // The main taxon for the page content and filtering
        $taxonKeys = explode('/', $taxonKeys);
        $category = $this->categoryRepository->findByKey(urldecode($taxonKeys[count($taxonKeys) - 1]));

        if (! $category) {
            if ($redirect = Redirect::from($request->path())) {
                return redirect()->to($redirect->to);
            }

            throw new NotFoundHttpException('No Taxon category found by slug ' . implode('/', $taxonKeys));
        }

        if ($request->anyFilled('price-from', 'price-to')) {
            $priceRanges = [
                IntegerConverter::convertDecimalToInteger($request->input('price-from', $request->input('price-to'))),
                IntegerConverter::convertDecimalToInteger($request->input('price-to', $request->input('price-from'))),
            ];

            sort($priceRanges);

            $this->gridRepository->filterByPrice((string) $priceRanges[0], (string) $priceRanges[1]);
        }

        if ($request->filled('sortPrice')) {
            $request->input('sortPrice') === 'priceDesc'
                ? $this->gridRepository->sortByPriceDesc()
                : $this->gridRepository->sortByPrice();
        }

        $activeTaxons = $this->getActiveTaxonFilters->get($category->getKey(), $request->all());
        $filterTaxons = $this->getAvailableTaxonFilters->get($category->getKey());

        return view('shop.catalog.taxon', [
            'category' => $category,
//            'taxonModel' => TaxonModel::findByKey($category->getKey()),
            'products' => $this->gridRepository->filterByTaxonKeys($activeTaxons->pluck('key'))->paginate(12)->getResults(),
            'filterTaxons' => $filterTaxons,
            'activeTaxons' => collect($activeTaxons->removeNode($category)->all()),
        ]);
    }

    /**
     * @param Taxon|null $taxon
     * @param Request $request
     * @return Collection
     */
    private function activeTaxons(Taxon $taxon, Request $request): NodeCollection
    {
        // Without any filtering active, the current taxon page is used for the product filtering
        $activeTaxons = new NodeCollection([$taxon]);

        // With filtering in effect, only products belonging to these filtered taxa, will be fetched
        if ($request->filled('taxon')) {
            $selectedTaxons = $this->taxonRepository->findManyByKeys((array)$request->input('taxon', []));

            /**
             * If any of the selected taxa belong to the same root as the main taxon, we filter down into the main taxon
             * and therefore omit the main taxon as filter and use the selected taxa as the only active filters instead.
             */
            foreach ($selectedTaxons as $selectedTaxon) {
                if ($selectedTaxon->getRootNode()->getNodeId() === $taxon->getRootNode()->getNodeId()) {
                    $activeTaxons = new NodeCollection([]);
                }
            }

            $activeTaxons = $activeTaxons->merge(
                $this->taxonRepository->findManyByKeys((array)$request->input('taxon', []))
            );
        }

        return $activeTaxons;
    }

    private function filterTaxons(Taxon $taxon): NodeCollection
    {
        $tree = $this->taxonRepository->getRootNodes();

        // Get all productgroup ids for this taxon
        $productGroupIds = $taxon->getProductGroupIds();

        $taxon->getChildNodes()->flatten()->each(function ($taxonChild) use (&$productGroupIds) {
            $productGroupIds = array_merge($productGroupIds, $taxonChild->getProductGroupIds());
        });

        $productGroupIds = array_values(array_unique($productGroupIds));

        $filterTaxons = $tree->shake(fn ($node) => array_intersect($node->getProductGroupIds(), $productGroupIds));

        // For categories, we want to start from the given taxon as the root - and not the 'real' root.
        // Therefore we exclude all ancestors from the given taxon which allows to only show the
        // nested taxa. This is purely a visual improvement for the filter.
        if (! $taxon->isRootNode() && ($ancestorIds = $taxon->pluckAncestorNodes('id'))) {

            // Keep the root node in the filter in order to keep our structure intact
            array_pop($ancestorIds);

            $filterTaxons = $filterTaxons
                ->prune(fn ($node) => ! in_array($node->getNodeId(), [$taxon->getNodeId(), ...$ancestorIds]));
        }

        return $filterTaxons;
    }
}
