<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog\Options;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Options\Application\SaveOptions;
use Thinktomorrow\Trader\Catalog\Options\Application\SaveOptionsPayload;
use Thinktomorrow\Trader\Catalog\Options\Domain\OptionRepository;

class SaveOptionsTest extends TestCase
{
    use DatabaseMigrations;
    use OptionTestHelpers;

    /** @test */
    public function it_can_save_product_options()
    {
        $productGroup = $this->createProductGroup();
        $this->defaultOptionSetup($productGroup->getId());

        $results = app(OptionRepository::class)->get($productGroup->getId());
        $this->assertCount(3, $results);

        $this->assertEquals(['nl' => 'blauw', 'en' => 'blue'], $results[0]->getValues());
        $this->assertEquals('blauw', $results[0]->getValue('nl'));
    }

    /** @test */
    public function it_can_update_an_option()
    {
        $productGroup = $this->createProductGroup();
        $this->defaultOptionSetup($productGroup->getId());

        $payload = (new SaveOptionsPayload($productGroup->getId()))
            ->add("1", ['nl' => 'blaaf', 'en' => 'blue'], "1")
            ->add("1", ['nl' => 'gruun', 'en' => 'green'], "2")
            ->add("2", ['nl' => 'melk', 'en' => 'milk'], "3");
        app(SaveOptions::class)->handle($payload);

        $results = app(OptionRepository::class)->get($productGroup->getId());
        $this->assertCount(3, $results);

        $this->assertEquals('blaaf', $results[0]->getValue('nl'));
        $this->assertEquals('gruun', $results[1]->getValue('nl'));
    }

    /** @test */
    public function it_deletes_option_when_no_longer_in_payload()
    {
        $productGroup = $this->createProductGroup();
        $this->defaultOptionSetup($productGroup->getId());

        $this->assertCount(3, app(OptionRepository::class)->get($productGroup->getId()));

        $payload = (new SaveOptionsPayload($productGroup->getId()))
            ->add("1", ['nl' => 'groen', 'en' => 'green'], "1");
        app(SaveOptions::class)->handle($payload);

        $results = app(OptionRepository::class)->get($productGroup->getId());
        $this->assertCount(1, $results);

        $this->assertEquals('groen', $results[0]->getValue('nl'));
    }
}
