<?php

namespace Tests\Unit\Model;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Events\TaxonomyCreated;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Taxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class TaxonomyTest extends TestCase
{
    public function test_it_can_be_created()
    {
        $taxonomy = Taxonomy::create(
            TaxonomyId::fromString('tax-001'),
            TaxonomyType::property
        );

        $taxonomy->addData(['foo' => 'bar']);

        $this->assertEquals([
            'taxonomy_id' => 'tax-001',
            'type' => 'property',
            'state' => 'online',
            'shows_as_grid_filter' => false,
            'shows_on_listing' => false,
            'allows_multiple_values' => false,
            'order' => 0,
            'data' => json_encode(['foo' => 'bar']),
        ], $taxonomy->getMappedData());

        $this->assertEquals([new TaxonomyCreated(TaxonomyId::fromString('tax-001'))], $taxonomy->releaseEvents());
    }

    public function test_it_can_change_type()
    {
        $taxonomy = $this->createdTaxonomy();

        $taxonomy->changeType(TaxonomyType::variant_property);

        $this->assertEquals(TaxonomyType::variant_property, $taxonomy->getType());
    }

    public function test_it_can_change_state()
    {
        $taxonomy = $this->createdTaxonomy();

        $taxonomy->changeState(TaxonomyState::offline);

        $this->assertEquals(TaxonomyState::offline, $taxonomy->getState());
    }

    public function test_it_can_change_order()
    {
        $taxonomy = $this->createdTaxonomy();

        $taxonomy->changeOrder(10);

        $this->assertEquals(10, $taxonomy->getOrder());
    }

    public function test_it_can_toggle_flags()
    {
        $taxonomy = $this->createdTaxonomy();

        $taxonomy->showAsGridFilter(true);
        $taxonomy->showOnListing(true);
        $taxonomy->allowMultipleValues(true);

        $this->assertTrue($taxonomy->showsAsGridFilter());
        $this->assertTrue($taxonomy->showsOnListing());
        $this->assertTrue($taxonomy->allowsMultipleValues());
    }

    public function test_it_can_be_built_from_mapped_data()
    {
        $taxonomy = Taxonomy::fromMappedData([
            'taxonomy_id' => 'tax-999',
            'type' => 'variant_property',
            'state' => 'offline',
            'shows_as_grid_filter' => true,
            'shows_on_listing' => false,
            'allows_multiple_values' => true,
            'order' => 3,
            'data' => json_encode(['foo' => 'bar']),
        ], [

        ]);

        $this->assertEquals('tax-999', $taxonomy->getMappedData()['taxonomy_id']);
        $this->assertEquals('variant_property', $taxonomy->getMappedData()['type']);
        $this->assertEquals(true, $taxonomy->showsAsGridFilter());
        $this->assertEquals(false, $taxonomy->showsOnListing());
        $this->assertEquals(true, $taxonomy->allowsMultipleValues());
    }

    private function createdTaxonomy(): Taxonomy
    {
        return Taxonomy::fromMappedData([
            'taxonomy_id' => 'tax-001',
            'type' => 'property',
            'state' => 'online',
            'shows_as_grid_filter' => false,
            'shows_on_listing' => false,
            'allows_multiple_values' => false,
            'order' => 0,
            'data' => json_encode([]),
        ], [

        ]);
    }
}
