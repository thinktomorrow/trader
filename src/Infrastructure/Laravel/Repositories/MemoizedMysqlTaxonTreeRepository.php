<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Thinktomorrow\Trader\Application\Taxon\Queries\CategoryRepository;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTree;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonTreeRepository;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxon\Exceptions\CouldNotFindTaxon;
use Thinktomorrow\Trader\TraderConfig;

class MemoizedMysqlTaxonTreeRepository implements TaxonTreeRepository, CategoryRepository
{
    private MysqlTaxonTreeRepository $taxonTreeRepository;

    /** @var TaxonTree[] - tree per locale */
    private static array $trees = [];

    private Locale $locale;

    public function __construct(MysqlTaxonTreeRepository $taxonTreeRepository, TraderConfig $traderConfig)
    {
        $this->taxonTreeRepository = $taxonTreeRepository;

        $this->locale = $traderConfig->getDefaultLocale();
    }

    public function setLocale(Locale $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

    public function findTaxonById(string $taxonId): TaxonNode
    {
        /** @var TaxonNode $taxonNode */
        $taxonNode = $this->getTree()->find(fn (TaxonNode $taxonNode) => $taxonNode->getId() == $taxonId);

        if (! $taxonNode) {
            throw new CouldNotFindTaxon('No taxon record found by id ' . $taxonId);
        }

        return $taxonNode;
    }

    public function findTaxonByKey(string $key): TaxonNode
    {
        /** @var TaxonNode $taxonNode */
        $taxonNode = $this->getTree()->find(fn (TaxonNode $taxonNode) => $taxonNode->getKey() == $key);

        if (! $taxonNode) {
            throw new CouldNotFindTaxon('No taxon record found by key ' . $key);
        }

        return $taxonNode;
    }

    public function getTree(): TaxonTree
    {
        $localeKey = $this->locale->get();

        if (isset(static::$trees[$localeKey])) {
            return static::$trees[$localeKey];
        }

        return static::$trees[$localeKey] = $this->taxonTreeRepository
            ->setLocale($this->locale)
            ->getTree();
    }

    public function getTreeByTaxonomy(string $taxonomyId): TaxonTree
    {
        $memoizeKey = $this->locale->get() . '_' . $taxonomyId;

        if (isset(static::$trees[$memoizeKey])) {
            return static::$trees[$memoizeKey];
        }

        return static::$trees[$memoizeKey] = $this->taxonTreeRepository
            ->setLocale($this->locale)
            ->getTreeByTaxonomy($taxonomyId);
    }

    public function getTreeByTaxonomies(array $taxonomyIds): TaxonTree
    {
        $memoizeKey = $this->locale->get() . '_' . implode('_', $taxonomyIds);

        if (isset(static::$trees[$memoizeKey])) {
            return static::$trees[$memoizeKey];
        }

        return static::$trees[$memoizeKey] = $this->taxonTreeRepository
            ->setLocale($this->locale)
            ->getTreeByTaxonomies($taxonomyIds);
    }

    public static function clear(): void
    {
        static::$trees = [];
    }
}
