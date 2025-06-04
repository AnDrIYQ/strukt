<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\EndPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollectionEntryUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Collection $collection;
    protected EndPoint $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->user = User::factory()->create(['role' => 'user']);
        $this->actingAs($this->user, 'sanctum');

        $this->collection = Collection::create([
            'name' => 'posts',
            'label' => 'Posts',
            'icon' => 'file-text',
            'singleton' => false,
            'schema' => [
                [
                    'name' => 'title',
                    'title' => 'Title',
                    'type' => 'value',
                    'multiple' => false,
                    'rules' => 'required|string|max:255',
                ],
            ],
        ]);

        $this->endpoint = EndPoint::create([
            'path' => 'update-post',
            'collection_id' => $this->collection->id,
            'type' => 'update',
            'role' => ['user'],
            'fields' => ['title'],
            'own_only' => false,
            'trigger_event' => false,
        ]);
    }

    public function test_user_can_update_entry()
    {
        $entryId = DB::table('collection_entries')->insertGetId([
            'collection_id' => $this->collection->id,
            'user_id' => $this->user->id,
            'data' => json_encode(['title' => 'Old Title']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/collections/{$this->collection->name}/{$this->endpoint->path}", [
            'ids' => [$entryId],
            'data' => [
                $entryId => ['title' => 'New Title']
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'updated' => 1]);

        $this->assertDatabaseHas('collection_entries', [
            'id' => $entryId,
        ]);

        $entry = DB::table('collection_entries')->find($entryId);
        $this->assertStringContainsString('New Title', $entry->data);
    }

    public function test_user_cannot_update_fields_not_allowed_by_endpoint()
    {
        $entryId = DB::table('collection_entries')->insertGetId([
            'collection_id' => $this->collection->id,
            'user_id' => $this->user->id,
            'data' => json_encode(['title' => 'Old']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/collections/{$this->collection->name}/{$this->endpoint->path}", [
            'ids' => [$entryId],
            'data' => [
                $entryId => ['invalid_field' => 'Hack']
            ]
        ]);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function own_only_user_cannot_update_others_entry(): void
    {
        $owner = User::factory()->create();
        $notOwner = User::factory()->create();

        $collection = Collection::factory()->create([
            'name' => 'own_only_collection',
            'singleton' => false,
            'schema' => [
                ['name' => 'title', 'type' => 'value', 'multiple' => false, 'rules' => 'required'],
            ]
        ]);

        $endpoint = EndPoint::create([
            'path' => 'update-entry',
            'collection_id' => $collection->id,
            'type' => 'update',
            'role' => ['user'],
            'own_only' => true,
            'fields' => ['title'],
        ]);

        $entryId = DB::table('collection_entries')->insertGetId([
            'collection_id' => $collection->id,
            'user_id' => $owner->id,
            'data' => json_encode(['title' => 'Initial']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($notOwner, 'sanctum');

        $response = $this->postJson("/api/collections/{$collection->name}/{$endpoint->path}", [
            'ids' => [$entryId],
            'data' => [
                $entryId => ['title' => 'New Title']
            ]
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'Недостатньо прав доступу',
        ]);
    }
}
