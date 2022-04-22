<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Domain\Common\Cash\IntegerConverter;
use Thinktomorrow\Trader\Application\Product\Grid\GridRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Thinktomorrow\Trader\Application\Taxon\Category\CategoryRepository;
use Thinktomorrow\Trader\Application\Taxon\Filter\TaxonFilterTreeComposer;
use Thinktomorrow\Trader\Infrastructure\Shop\RuntimeExceptions\FoundRouteAsRedirect;

class CategoryController
{
    protected GridRepository $gridRepository;
    protected CategoryRepository $categoryRepository;
    protected TaxonFilterTreeComposer $taxonFilterTreeComposer;
    protected ?TaxonTree $activeTaxons = null;

    public function __construct(GridRepository $gridRepository, CategoryRepository $categoryRepository, TaxonFilterTreeComposer $taxonFilterTreeComposer)
    {
        $this->gridRepository = $gridRepository;
        $this->categoryRepository = $categoryRepository;
        $this->taxonFilterTreeComposer = $taxonFilterTreeComposer;
    }

    public function show(string $taxonKeys, Request $request)
    {
        try{
            $taxon = $this->extractTaxonFromSlug($taxonKeys, $request);
        } catch(FoundRouteAsRedirect $e) {
            return redirect()->to($e->getRedirect());
        }

        $products = $this->getProducts($taxon, $request);
        $filterTaxons = $this->taxonFilterTreeComposer->getAvailableFilters($taxon->getKey());

        return view('shop.catalog.taxon', [
            'taxon' => $taxon,
            'products' => $products,
            'filterTaxons' => $filterTaxons,
            'activeTaxons' => collect($this->getActiveTaxons($taxon, $request)->removeNode($taxon)->all()),
        ]);
    }

    protected function extractTaxonFromSlug(string $taxonKeys, Request $request): TaxonNode
    {
        // The main taxon for the page content and filtering
        $taxonKeys = explode('/', $taxonKeys);
        $taxon = $this->categoryRepository->findTaxonByKey(urldecode($taxonKeys[count($taxonKeys) - 1]));

        if (! $taxon) {

            if ($redirect = Redirect::from($request->path())) {
                throw (new FoundRouteAsRedirect($request->path()))->setRedirect($redirect->to);
            }

            throw new NotFoundHttpException('No Taxon category found by slug ' . implode('/', $taxonKeys));
        }

        return $taxon;
    }

    protected function getProducts(TaxonNode $taxon, Request $request): LengthAwarePaginator
    {
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

        return $this->gridRepository
            ->filterByTaxonKeys($this->getActiveTaxons($taxon, $request)->pluck('key'))
            ->paginate(12)
            ->getResults();
    }

    protected function getActiveTaxons(TaxonNode $taxon, Request $request)
    {
        if($this->activeTaxons) {
            return $this->activeTaxons;
        }

        return $this->activeTaxons = $this->taxonFilterTreeComposer->getActiveFilters($taxon->getKey(), $request->all());
    }
}
