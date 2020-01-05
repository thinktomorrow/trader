<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Common;

class Config
{
    private static $config;

    /** @var string */
    private $filepath;

    public function __construct($filepath = null)
    {
        $this->filepath = $filepath ?: __DIR__.'/../../config/trader.php';

        $this->loadFile();
    }

    /**
     * @param $key
     * @param null $default
     *
     * @return null
     */
    public function get($key, $default = null)
    {
        return (isset(static::$config[$key])) ? static::$config[$key] : $default;
    }

    /**
     * Set a config value at runtime.
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        static::$config[$key] = $value;
    }

    public function refreshSource($filepath)
    {
        static::$config = null;
        $this->filepath = $filepath;
    }

    private function loadFile()
    {
        if (static::$config) {
            return;
        }

        static::$config = include $this->filepath;
    }
}
