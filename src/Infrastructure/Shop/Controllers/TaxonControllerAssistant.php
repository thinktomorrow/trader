<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\Controllers;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Domain\Common\Cash\IntegerConverter;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;
use Thinktomorrow\Trader\Infrastructure\Shop\RuntimeExceptions\FoundRouteAsRedirect;

trait TaxonControllerAssistant
{
    protected ?TaxonTree $activeTaxons = null;

    protected function extractTaxonFromSlug(Locale $locale, string $taxonKeys): TaxonNode
    {
        // The main taxon for the page content and filtering
        $taxonKeys = explode('/', $taxonKeys);
        $taxonKey = urldecode($taxonKeys[count($taxonKeys) - 1]);

        try {
            $taxonNode = $this->categoryRepository->setLocale($locale)->findTaxonByKey($taxonKey);

            if (! $taxonNode->showOnline()) {
                throw new CouldNotFindTaxon('Taxon ' . $taxonKey . ' is offline.');
            }

            return $taxonNode;
        } catch (CouldNotFindTaxon $e) {
            if ($redirect = $this->redirectRepository->find($locale, $taxonKey)) {
                throw (new FoundRouteAsRedirect($this->getTaxonUrl($redirect->getLocale(), $redirect->getFrom())))->setRedirect($this->getTaxonUrl($redirect->getLocale(),$redirect->getTo()));
            }

            throw new NotFoundHttpException('No Taxon category found by slug ' . implode('/', $taxonKeys));
        }
    }

    protected function getTaxonUrl(Locale $locale, string $taxon_key): string
    {
        return $locale->get() .'/'. $taxon_key;
    }

    protected function getProducts(TaxonNode $taxon, Request $request): LengthAwarePaginator
    {
        if ($request->anyFilled('price-from', 'price-to')) {
            $priceRanges = [
                $request->input('price-from') ? (string) IntegerConverter::convertDecimalToInteger($request->input('price-from')) : null,
                $request->input('price-to') ? (string) IntegerConverter::convertDecimalToInteger($request->input('price-to')) : null,
            ];

            // Sort in ascending order when both prices are filled in.
            if (isset($priceRanges[0], $priceRanges[1])) {
                sort($priceRanges);
            }

            $this->gridRepository->filterByPrice($priceRanges[0], $priceRanges[1]);
        }

        if ($request->filled('sort')) {
            if ($request->input('sort') === 'priceDesc') {
                $this->gridRepository->sortByPriceDesc();
            } elseif ($request->input('sort') === 'priceAsc') {
                $this->gridRepository->sortByPrice();
            } elseif ($request->input('sort') === 'labelDesc') {
                $this->gridRepository->sortByLabelDesc();
            } elseif ($request->input('sort') === 'labelAsc') {
                $this->gridRepository->sortByLabel();
            }
        }

        return $this->gridRepository
            ->filterByTaxonIds($this->getActiveTaxons($taxon, $request->input('taxon', []))->pluck('id'))
            ->paginate(12)
            ->getResults();
    }

    protected function getActiveTaxons(TaxonNode $taxon, array $taxonKeys)
    {
        if ($this->activeTaxons) {
            return $this->activeTaxons;
        }

        return $this->activeTaxons = $this->taxonFilterTreeComposer->getActiveFilters($this->currentLocale->get(), $taxon->getKey(), $taxonKeys);
    }
}
