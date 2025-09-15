<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Tree;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Vine\Node;

interface TaxonNode extends Node
{
    public function setLocale(Locale $locale): static;

    public static function fromMappedData(array $state, array $taxonKeys): static;

    public function getNodeId($key = null, $default = null): string;

    public function getParentNodeId(): ?string;

    public function getId(): string;

    public function getTaxonomyId(): string;

    public function getKey(?string $locale = null): ?string; // Localized key based on the locale

    public function getLabel(?string $locale = null): string;

    public function getContent(?string $locale = null): ?string;

    public function showOnline(): bool;

    public function getProductIds(): array;

    public function getGridProductIds(): array;

    public function getProductCount(array $productIds): int;

    public function getProductTotal(): int;

    public function getUrl(?string $locale = null): string;

    public function getBreadCrumbs(): array;

    public function getBreadCrumbLabelWithoutRoot(?string $locale = null): string;

    public function getBreadCrumbLabel(?string $locale = null, bool $withoutRoot = false): string;

    public function getImages(): iterable;
}
