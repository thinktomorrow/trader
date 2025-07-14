<?php

namespace Thinktomorrow\Trader\Domain\Model\Taxonomy;

enum TaxonomyType: string
{
    case property = 'property';
    case variant_property = 'variant_property';
    case category = 'category';
    case google_category = 'google_category';
    case collection = 'collection';
    case tag = 'tag';
}
