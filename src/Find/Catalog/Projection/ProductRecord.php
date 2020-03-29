<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Find\Catalog\Projection;

class ProductRecord
{
    /** @var string */
    private $id;

    /** @var array */
    private $columns;

    /** @var array */
    private $data;

    /** @var array */
    private $translations;

    public function __construct(string $id, array $columns, array $data, array $translations)
    {
        $this->id = $id;
        $this->columns = $columns;
        $this->data = $data;
        $this->translations = $translations;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function translations(): array
    {
        return $this->translations;
    }
}
