<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\EndPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CollectionEntrySearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Collection $collection;
    protected EndPoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
        $this->actingAs($this->user, 'sanctum');

        $this->collection = Collection::create([
            'name' => 'searchables',
            'label' => 'Searchables',
            'icon' => 'search',
            'singleton' => false,
            'schema' => [
                [
                    'name' => 'title',
                    'type' => 'value',
                    'multiple' => false,
                    'rules' => 'required|string|max:255',
                ],
                [
                    'name' => 'description',
                    'type' => 'value',
                    'multiple' => false,
                    'rules' => 'nullable|string|max:500',
                ]
            ]
        ]);

        $this->endpoint = EndPoint::create([
            'collection_id' => $this->collection->id,
            'path' => 'search-items',
            'type' => 'search',
            'role' => ['user'],
            'fields' => ['title', 'description'],
            'own_only' => false,
            'trigger_event' => false,
        ]);

        DB::table('collection_entries')->insert([
            [
                'collection_id' => $this->collection->id,
                'user_id' => $this->user->id,
                'data' => json_encode([
                    'title' => 'Test Title 1',
                    'description' => 'First record for search'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'collection_id' => $this->collection->id,
                'user_id' => $this->user->id,
                'data' => json_encode([
                    'title' => 'Another Title',
                    'description' => 'Second record'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    public function test_user_can_search_entries()
    {
        $response = $this->postJson("/api/collections/{$this->collection->name}/{$this->endpoint->path}", [
            'filters' => ['title' => 'Test']
        ]);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
        $response->assertJsonFragment(['title' => 'Test Title 1']);
    }

    public function test_user_gets_all_if_no_filters()
    {
        $response = $this->postJson("/api/collections/{$this->collection->name}/{$this->endpoint->path}", []);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.data');
    }

    public function test_unauthorized_user_cannot_access_protected_search()
    {
        $this->endpoint->update(['role' => ['admin']]);

        $response = $this->postJson("/api/collections/{$this->collection->name}/{$this->endpoint->path}", []);
        $response->assertStatus(403);
    }

    public function test_own_only_filtering_works()
    {
        $this->endpoint->update(['own_only' => true]);

        $other = User::factory()->create(['role' => 'user']);
        DB::table('collection_entries')->insert([
            'collection_id' => $this->collection->id,
            'user_id' => $other->id,
            'data' => json_encode(['title' => 'Other', 'description' => 'Foreign']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/collections/{$this->collection->name}/{$this->endpoint->path}", []);
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.data');
    }

    public function test_user_can_search_entries_with_populate()
    {
        $relatingCollection = Collection::create([
            'name' => 'for-relating',
            'label' => 'For Relating',
            'icon' => 'search',
            'singleton' => false,
            'schema' => [
                [
                    'name' => 'title',
                    'type' => 'value',
                    'multiple' => false,
                    'rules' => 'required',
                ],
                [
                    'name' => 'related_item',
                    'type' => 'relation',
                    'collection' => $this->collection->name,
                    'multiple' => false,
                    'rules' => '',
                ]
            ]
        ]);
        $entry = DB::table('collection_entries')->first();
        DB::table('collection_entries')->insert([
            'collection_id' => $relatingCollection->id,
            'user_id' => $this->user->id,
            'data' => json_encode([
                'title' => 'WithRel',
                'related_item' => $entry->id,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        EndPoint::create([
            'collection_id' => $relatingCollection->id,
            'path' => 'search',
            'type' => 'search',
            'role' => ['user'],
            'fields' => ['title', 'related_item'],
            'own_only' => false,
            'trigger_event' => false,
        ]);

        $response = $this->getJson("/api/collections/{$relatingCollection->name}/search?populate=related_item&filters[title]=WithRel");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
        $response->assertJsonPath('data.data.0.related_item.title', 'Test Title 1');
    }
}
