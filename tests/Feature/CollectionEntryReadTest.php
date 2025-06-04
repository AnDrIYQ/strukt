<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\EndPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CollectionEntryReadTest extends TestCase
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
            'name' => 'posts',
            'label' => 'Posts',
            'icon' => 'text',
            'singleton' => false,
            'schema' => [
                [
                    'name' => 'title',
                    'type' => 'value',
                    'multiple' => false,
                    'rules' => 'required',
                ]
            ]
        ]);

        $this->endpoint = EndPoint::create([
            'collection_id' => $this->collection->id,
            'path' => 'read',
            'type' => 'read',
            'role' => ['user'],
            'fields' => ['title'],
            'own_only' => false,
            'trigger_event' => false,
        ]);

        DB::table('collection_entries')->insert([
            'collection_id' => $this->collection->id,
            'user_id' => $this->user->id,
            'data' => json_encode(['title' => 'First Post']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_user_can_read_collection_entries()
    {
        $response = $this->getJson("/api/collections/{$this->collection->name}/read");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
        $response->assertJsonPath('data.data.0.title', 'First Post');
    }

    public function test_user_cannot_see_other_entries_when_only_own()
    {
        $this->endpoint->update(['own_only' => true]);

        $otherUser = User::factory()->create();
        DB::table('collection_entries')->insert([
            'collection_id' => $this->collection->id,
            'user_id' => $otherUser->id,
            'data' => json_encode(['title' => 'Other Post']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson("/api/collections/{$this->collection->name}/read");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.data');
        $response->assertJsonMissing(['title' => 'Other Post']);
    }

    public function test_singleton_collection_returns_one_entry()
    {
        $this->collection->update(['singleton' => true]);

        $response = $this->getJson("/api/collections/{$this->collection->name}/read");

        $response->assertStatus(200);
        $response->assertJsonPath('title', 'First Post');
    }

    public function test_user_can_read_with_populate()
    {
        $related = Collection::create([
            'name' => 'authors',
            'label' => 'Authors',
            'icon' => 'user',
            'singleton' => false,
            'schema' => [
                [
                    'name' => 'name',
                    'type' => 'value',
                    'multiple' => false,
                    'rules' => 'required',
                ]
            ]
        ]);

        $authorId = DB::table('collection_entries')->insertGetId([
            'collection_id' => $related->id,
            'user_id' => $this->user->id,
            'data' => json_encode(['name' => 'John Writer']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->collection->update([
            'schema' => array_merge($this->collection->schema, [
                [
                    'name' => 'author',
                    'type' => 'relation',
                    'collection' => 'authors',
                    'multiple' => false,
                    'rules' => 'required',
                ]
            ])
        ]);

        DB::table('collection_entries')->insert([
            'collection_id' => $this->collection->id,
            'user_id' => $this->user->id,
            'data' => json_encode([
                'title' => 'Post with Author',
                'author' => $authorId,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->endpoint->update([
            'fields' => ['title', 'author'],
        ]);

        $response = $this->getJson("/api/collections/{$this->collection->name}/read?populate=author");

        $response->assertStatus(200);
        $response->assertJsonPath('data.data.0.title', 'Post with Author');
        $response->assertJsonPath('data.data.0.author.name', 'John Writer');
    }
}
