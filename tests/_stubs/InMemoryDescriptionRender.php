<?php

namespace Thinktomorrow\Trader\Tests\Stubs;

use Thinktomorrow\Trader\Common\Domain\Description;
use Thinktomorrow\Trader\Common\Domain\DescriptionRender;

class InMemoryDescriptionRender implements DescriptionRender
{
    private $translations = [
        'nl' => [
            'foobar' => 'Dit is een bericht',
        ],
        'en' => [
            'foobar' => 'This is a message',
        ],
    ];

    private $description;

    private $defaultLocale = 'nl';

    public function description(Description $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function locale($locale = null): string
    {
        if(!$this->description) return '';
        if(!$locale) $locale = $this->defaultLocale;

        $translation = $this->description->key();

        if( ! isset($this->translations[$locale]))
        {
            if($locale == $this->defaultLocale || !isset($this->translations[$this->defaultLocale])) return '';

            $locale = $this->defaultLocale;
        }

        $translations = $this->translations[$locale];

        if(isset($translations[$this->description->key()]))
        {
            $translation = $translations[$this->description->key()];
        }

        return sprintf($translation, $this->description->values());
    }
}
