<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\EndPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructureEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function it_returns_only_accessible_collections_with_filtered_schema()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user, 'sanctum');

        $collection = Collection::create([
            'name' => 'articles',
            'label' => 'Articles',
            'icon' => 'file-text',
            'singleton' => false,
            'schema' => [
                ['name' => 'title', 'type' => 'value'],
                ['name' => 'body', 'type' => 'value'],
                ['name' => 'secret', 'type' => 'value'],
            ],
        ]);

        EndPoint::create([
            'collection_id' => $collection->id,
            'path' => 'read-articles',
            'type' => 'read',
            'role' => ['user'],
            'fields' => ['title', 'body'],
            'own_only' => false,
            'trigger_event' => false,
        ]);

        $response = $this->getJson('/api/structure');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'articles']);
        $response->assertJsonPath('0.schema.*.name', ['title', 'body']);
        $response->assertJsonPath('0.endpoints.0.fields', ['title', 'body']);
    }

    public function guest_user_sees_only_public_collections_and_fields()
    {
        $publicCollection = Collection::create([
            'name' => 'news',
            'label' => 'News',
            'icon' => 'newspaper',
            'singleton' => false,
            'schema' => [
                ['name' => 'headline', 'type' => 'value'],
                ['name' => 'content', 'type' => 'value'],
                ['name' => 'secret', 'type' => 'value'],
            ],
        ]);

        $privateCollection = Collection::create([
            'name' => 'admin-logs',
            'label' => 'Logs',
            'icon' => 'alert-triangle',
            'singleton' => false,
            'schema' => [
                ['name' => 'event', 'type' => 'value'],
            ],
        ]);

        EndPoint::create([
            'collection_id' => $publicCollection->id,
            'path' => 'read-news',
            'type' => 'read',
            'role' => ['public'],
            'fields' => ['headline'],
            'own_only' => false,
            'trigger_event' => false,
        ]);

        EndPoint::create([
            'collection_id' => $privateCollection->id,
            'path' => 'read-logs',
            'type' => 'read',
            'role' => ['admin'],
            'fields' => ['event'],
            'own_only' => false,
            'trigger_event' => false,
        ]);

        $response = $this->getJson('/api/structure');

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment(['name' => 'news']);
        $response->assertJsonPath('0.schema.*.name', ['headline']);
        $response->assertJsonMissing(['name' => 'admin-logs']);
    }

    public function returns_full_schema_when_fields_are_null()
    {
        $collection = Collection::create([
            'name' => 'events',
            'label' => 'Events',
            'icon' => 'calendar',
            'singleton' => false,
            'schema' => [
                ['name' => 'title', 'type' => 'value'],
                ['name' => 'date', 'type' => 'value'],
                ['name' => 'location', 'type' => 'value'],
            ],
        ]);

        EndPoint::create([
            'collection_id' => $collection->id,
            'path' => 'read-events',
            'type' => 'read',
            'role' => ['public'],
            'fields' => null,
            'own_only' => false,
            'trigger_event' => false,
        ]);

        $response = $this->getJson('/api/structure');

        $response->assertStatus(200);
        $response->assertJsonCount(1);

        $response->assertJsonPath('0.name', $collection->name);
        $response->assertJsonPath('0.schema', []);
        $response->assertJsonCount(0, '0.schema');
        $response->assertJsonCount(1, '0.endpoints');
    }
}
