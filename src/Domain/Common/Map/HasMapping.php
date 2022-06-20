<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Domain\Common\Map;

trait HasMapping
{
    protected array $mapping;

    protected function setMapping(array $mapping): void
    {
        foreach ($mapping as $mappable) {
            $this->assertClassIsMappable($mappable);
            $mapping[$mappable::getMapKey()] = $mappable;
        }
    }

    protected function findMappable(string $key): string
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
