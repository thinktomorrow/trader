<?php

namespace Thinktomorrow\Trader\Purchase\Cart\Domain;

use Illuminate\Support\Collection;

class CartNotes extends Collection
{
    public function render($tags = [], string $locale = null): string
    {
        if(!is_array($tags)) $tags = [$tags];

        return $this->map(function(CartNote $note) use($tags, $locale){
            return $note->render($tags, $locale);
        })->implode('');
    }
}
