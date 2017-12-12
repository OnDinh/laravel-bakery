<?php

namespace Bakery\Tests\Feature;

use Schema;
use Eloquent;
use Bakery\Tests\Stubs;
use Bakery\Tests\TestCase;
use Bakery\Tests\WithDatabase;
use Bakery\Http\Controller\BakeryController;
use Bakery\Exceptions\TooManyResultsException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EntityQueryTest extends TestCase
{
    use WithDatabase;

    protected function setUp()
    {
        parent::setUp();
        Eloquent::unguard();
        $this->migrateDatabase();
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $this->setupDatabase($app);
    }

    /** @test */
    public function it_returns_single_entity()
    {
        Stubs\Model::create();

        $query = '
            query {
                model(id: 1) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [ 'model' ] ]);
        $this->assertEquals(json_decode($response->getContent())->data->model->id, '1');
    }

    /** @test */
    public function it_returns_single_entity_for_a_lookup_field()
    {
        Stubs\Model::create(['slug' => 'test-model']);
    
        $query = '
            query {
                model(slug: "test-model") {
                    id
                }
            }
        ';
    
        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => [ 'model' ] ]);
        $this->assertEquals(json_decode($response->getContent())->data->model->id, '1');
    }

    /** @test */
    public function it_returns_null_when_there_are_no_results()
    {
        Stubs\Model::create(['slug' => 'test-model']);

        $query = '
            query {
                model(slug: "foo") {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $response->assertJsonFragment(['model' => null]);
    }

    /** @test */
    public function it_throws_too_many_results_exception_if_lookup_is_not_specific_enough()
    {
        $this->expectException(TooManyResultsException::class);

        Stubs\Model::create(['slug' => 'test-model']);
        Stubs\Model::create(['slug' => 'test-model']);

        $query = '
            query {
                model(slug: "test-model") {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
    }

    /** @test */
    public function it_can_lookup_entities_by_relational_fields()
    {
        $user = Stubs\User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'secret',
        ]);

        $user->posts()->create([
            'title' => 'Hello world!',
            'slug'  => 'hello-world',
        ]);

        $query = '
            query {
                post(user: { email: "john.doe@example.com"}) {
                    id
                }
            }
        ';

        $response = $this->json('GET', '/graphql', ['query' => $query]);
        $this->assertEquals(json_decode($response->getContent())->data->post->id, '1');
    }
}
