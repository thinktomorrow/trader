<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Taxon\Tree;

use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Vine\Node;

interface TaxonNode extends Node
{
    public function setLocale(Locale $locale): void;
    public static function fromMappedData(array $state): static;

    public function getNodeId($key = null, $default = null): string;
    public function getParentNodeId(): ?string;
    public function getId(): string;
    public function getKey(): string;
    public function getLabel(): string;
    public function getContent(): ?string;

    public function showOnline(): bool;
    public function getProductIds(): array;
    public function getUrl(): string;

    public function getBreadCrumbs(): array;
    public function getBreadCrumbLabelWithoutRoot(): string;
    public function getBreadCrumbLabel(bool $withoutRoot = false): string;
    public function getImages(): iterable;
}
