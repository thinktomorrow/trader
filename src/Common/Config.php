<?php

namespace Thinktomorrow\Trader\Common;

class Config
{
    private static $config;

    /**
     * @var string
     */
    private $filepath;

    public function __construct($filepath = null)
    {
        $this->filepath = $filepath ?: __DIR__.'/../../config/trader.php';
    }

    public function get($key, $default = null)
    {
        $this->loadFile();

        return (isset(static::$config[$key])) ? static::$config[$key] : $default;
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
