<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxonomy;

use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

final class CreateTaxonomy
{
    private string $taxonomyType;
    private bool $showsAsGridFilter;
    private bool $showsOnListing;
    private bool $allowsMultipleValues;
    private array $data;

    public function __construct(string $taxonomyType, bool $showsAsGridFilter, bool $showsOnListing, bool $allowsMultipleValues, array $data)
    {
        $this->taxonomyType = $taxonomyType;
        $this->showsAsGridFilter = $showsAsGridFilter;
        $this->showsOnListing = $showsOnListing;
        $this->allowsMultipleValues = $allowsMultipleValues;
        $this->data = $data;
    }

    public function getTaxonomyType(): TaxonomyType
    {
        return TaxonomyType::from($this->taxonomyType);
    }

    public function showsAsGridFilter(): bool
    {
        return $this->showsAsGridFilter;
    }

    public function showsOnListing(): bool
    {
        return $this->showsOnListing;
    }

    public function allowsMultipleValues(): bool
    {
        return $this->allowsMultipleValues;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
