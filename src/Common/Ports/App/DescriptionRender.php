<?php

namespace Thinktomorrow\Trader\Common\Ports\App;

use Thinktomorrow\Trader\Common\Domain\Description;

class DescriptionRender
{
    public function locale(Description $description, $locale = 'nl')
    {
        // TODO
        // SHOULD BECOME SOMETHING AS trans() SO need to put it in ports/app/ ?
        // return trans($description->key(),$description->values(),$locale);
        // SHOULD ALSO ACCOUNT FOR TRANSLATION ALREADY GIVEN? (e.g. FROM DB)
        return printf($description->key(), $description->values());
    }
}
