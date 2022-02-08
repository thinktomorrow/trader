<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Domain;

use Illuminate\Support\Arr;

trait HasDataAttribute
{
    protected function data(string $key, $default = null)
    {
        // TODO: remove container here, put it in repository and pass context to this object instead.
        $language = app()->make(Context::class)->getLocale()->getLanguage();

        // First we search for localized content
        return Arr::get(
            $this->data,
            $key . '.' . $language,
            Arr::get($this->data, $key, $default)
        );
    }
}
