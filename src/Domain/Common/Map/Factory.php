<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Map;

class Factory
{
    protected array $mapping;

    public function __construct(array $mapping)
    {
        foreach ($mapping as $mappable) {
            $this->assertClassIsMappable($mappable);
            $this->mapping[$mappable::getMapKey()] = $mappable;
        }
    }

    public function findMappable(string $key): string
    {
        if (! isset($this->mapping[$key])) {
            throw new \RuntimeException('No mappable class found by key ' . $key);
        }

        return $this->mapping[$key];
    }

    private function assertClassIsMappable(string $class): void
    {
        if (! (new \ReflectionClass($class))->implementsInterface(Mappable::class)) {
            throw new \InvalidArgumentException($class.' should implement '.Mappable::class);
        }
    }
}
