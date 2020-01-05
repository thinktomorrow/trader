<?php

namespace Thinktomorrow\Trader\Find\Catalog\Reads;

use Thinktomorrow\MagicAttributes\HasMagicAttributes;

class DefaultProduct implements Product
{
    use HasMagicAttributes;

    private $id;

    /** @var array */
    private $data;

    public function __construct($id, array $data)
    {
        $this->id = $id;
        $this->data = $data;
    }

    public function id()
    {
        return $this->id;
    }

    public function data($key, $default = null)
    {
        return $this->attr('data.'.$key, $default);
    }

    public function set($key, $value): Product
    {
        $this->data[$key] = $value;
    }
}
