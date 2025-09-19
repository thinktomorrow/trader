<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Shop\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Domain\Common\Cash\IntegerConverter;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;
use Thinktomorrow\Trader\Infrastructure\Shop\RuntimeExceptions\FoundRouteAsRedirect;

trait TaxonControllerAssistant
{
    protected ?TaxonTree $activeTaxa = null;

    /**
     * Holds the unfiltered product and variant IDs within the grid.
     *
     * Represents the "raw" result set after applying only basic filters
     * such as main category (taxon), without applying additional
     * filters like attributes, options, or other 'subfilters'.
     *
     * @var string[]
     */
    protected array $baseProductAndVariantIds = [];

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
                throw (new FoundRouteAsRedirect($this->getTaxonUrl($redirect->getLocale(), $redirect->getFrom())))->setRedirect($this->getTaxonUrl($redirect->getLocale(), $redirect->getTo()));
            }

            throw new NotFoundHttpException('No Taxon category found by slug ' . implode('/', $taxonKeys));
        }
    }

    protected function getTaxonUrl(Locale $locale, string $taxon_key): string
    {
        return $locale->get() . '/' . $taxon_key;
    }

    protected function getProducts(?TaxonNode $taxon, Request $request): LengthAwarePaginator
    {
        // Set the base product and variant IDs for the current grid context
        $this->setBaseProductAndVariantIds($taxon);

        // Filter by price range
        if ($request->anyFilled('price-from', 'price-to')) {
            $priceFrom = $request->filled('price-from')
                ? (string)IntegerConverter::convertDecimalToInteger($request->input('price-from'))
                : null;

            $priceTo = $request->filled('price-to')
                ? (string)IntegerConverter::convertDecimalToInteger($request->input('price-to'))
                : null;

            if ($priceFrom !== null && $priceTo !== null && $priceFrom > $priceTo) {
                [$priceFrom, $priceTo] = [$priceTo, $priceFrom]; // swap if needed
            }

            $this->gridRepository->filterByPrice($priceFrom, $priceTo);
        }

        // Sorting results
        $sortMap = [
            'priceDesc' => 'sortByPriceDesc',
            'priceAsc' => 'sortByPrice',
            'labelDesc' => 'sortByLabelDesc',
            'labelAsc' => 'sortByLabel',
        ];

        if ($request->filled('sort') && isset($sortMap[$request->input('sort')])) {
            $this->gridRepository->{$sortMap[$request->input('sort')]}();
        }

        $this->applyTaxonFilter($request->input('taxon', []));
        $this->applyTaxonFilter($request->input('variant_taxon', []), true);

        return $this->gridRepository
            ->paginate(12)
            ->getResults();
    }

    protected function applyTaxonFilter(array $keys, bool $isVariant = false): void
    {
        if (empty($keys)) {
            return;
        }

        $ids = $this->taxonFilterTreeComposer
            ->getFiltersFromKeys($this->currentLocale->get(), $keys)
            ->pluck('id');

        $isVariant
            ? $this->gridRepository->filterByVariantTaxonIds($ids)
            : $this->gridRepository->filterByTaxonIds($ids);
    }

    protected function getActiveTaxa(TaxonNode $taxon, array $taxonKeys)
    {
        if ($this->activeTaxa) {
            return $this->activeTaxa;
        }

        return $this->activeTaxa = $this->taxonFilterTreeComposer->getActiveFilters($this->currentLocale->get(), [$taxon->getKey()], $taxonKeys);
    }

    /**
     * Sets the unfiltered list of product and variant IDs within a grid.
     *
     * This represents a "raw" product count: it applies only the optional main
     * category (taxon), but excludes further filtering like attributes,
     * options, or other detailed characteristics.
     *
     * @param TaxonNode|null $taxon Optional taxon to limit the grid to a specific category.
     */
    protected function setBaseProductAndVariantIds(?TaxonNode $taxon): void
    {
        $totalResult = $this->gridRepository;

        if ($taxon) {
            $totalResult->filterByTaxonIds([$taxon->getNodeId()]);
        }

        $this->baseProductAndVariantIds = $totalResult->getResultingIds();
    }
}
