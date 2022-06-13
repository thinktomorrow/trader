<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Taxon\Redirect\Redirect;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlRedirectRepository;

final class RedirectRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_redirect()
    {
        foreach ($this->repositories() as $repository) {
            $repository->save(new Redirect('from', 'to'));

            $redirect = $repository->find('from');

            $this->assertEquals('from', $redirect->getFrom());
            $this->assertEquals('to', $redirect->getTo());
            $this->assertNotNull($redirect->getId());
            $this->assertInstanceOf(\DateTime::class, $redirect->getCreatedAt());

            $this->assertCount(1, $repository->getAllTo('to'));
            $this->assertEquals($redirect, $repository->getAllTo('to')[0]);
        }
    }

    /** @test */
    public function non_found_redirect_returns_null()
    {
        foreach ($this->repositories() as $repository) {
            $this->assertNull($repository->find('xxx'));
        }
    }

    /** @test */
    public function it_ignores_a_slash_when_finding_a_redirect()
    {
        foreach ($this->repositories() as $repository) {
            $repository->save(new Redirect('/from', '/to'));
            $this->assertNotNull($repository->find('from'));
        }
    }

    /** @test */
    public function it_can_update_a_redirect()
    {
        foreach ($this->repositories() as $repository) {
            $repository->save(new Redirect('from', 'to'));

            $redirect = $repository->find('from');
            $redirect->changeTo('/new/to');
            $repository->save($redirect);

            // Updated redirect
            $redirect = $repository->find('from');

            $this->assertEquals('from', $redirect->getFrom());
            $this->assertEquals('new/to', $redirect->getTo());
        }
    }

    /** @test */
    public function existing_redirects_are_adjusted_to_the_new_target()
    {
        foreach ($this->repositories() as $repository) {
            $repository->save(new Redirect('/existing/from', '/existing/to'));
            $repository->save(new Redirect('/existing/to', '/existing/to/new'));

            $existingRedirect = $repository->find('/existing/from');
            $redirect = $repository->find('/existing/to');

            $this->assertEquals('existing/from', $existingRedirect->getFrom());
            $this->assertEquals('existing/to/new', $existingRedirect->getTo());
            $this->assertEquals('existing/to', $redirect->getFrom());
            $this->assertEquals('existing/to/new', $redirect->getTo());
        }
    }

    /** @test */
    public function it_removed_redirects_pointing_from_and_to_the_new_target()
    {
        foreach ($this->repositories() as $repository) {
            $repository->save(new Redirect('/existing/from', '/existing/to'));
            $repository->save(new Redirect('/existing/to', '/existing/from'));

            $this->assertNull($repository->find('/existing/from'));
            $this->assertNotNull($repository->find('/existing/to'));
        }
    }

    private function repositories(): \Generator
    {
        yield new MysqlRedirectRepository();
    }
}
