<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Catalog\Products\Domain;

use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Catalog\Options\Domain\Options;
use Thinktomorrow\Trader\Catalog\Taxa\Domain\Taxon;

interface ProductGroup
{
    public function getId(): string;

    /** The products that should be shown on the catalog pages. Usually this is just one product. */
    public function getGridProducts(): Collection;

    /**
     * All the products belonging to this product group.
     */
    public function getProducts(): Collection;

    /**
     * Taxonomy values such as collections, category pages, vendors, ...
     */
    public function getTaxonomy(): Collection;

    /**
     * The main taxon where the products are structured in.
     * This is usually the 'category' tree
     */
    public function getCatalogTaxon(): ?Taxon;

    /** All the available options and their existing values. */
    public function getOptions(): Options;

    public function getTitle(): ?string;

    public function getIntro(): ?string;

    public function getContent(): ?string;

    public function getImages(): Collection;

    public function isVisitable(): bool;
}
