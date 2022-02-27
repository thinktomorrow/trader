<?php

namespace Thinktomorrow\Trader\Application\Common;

trait RendersData
{
    protected function data(string $key, string $language = null, $default = null)
    {
        return DataRenderer::get($this->data, $key, $language, $default);
    }
}
