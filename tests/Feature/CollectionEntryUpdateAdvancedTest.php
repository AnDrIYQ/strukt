<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use App\Events\CollectionChangedEvent;
use App\Models\Collection;
use App\Models\EndPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollectionEntryUpdateAdvancedTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $collection;
    protected $endpoint;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Event::fake();

        $this->user = User::factory()->create(['role' => 'user']);
        $this->actingAs($this->user, 'sanctum');

        $this->collection = Collection::create([
            'name' => 'files',
            'label' => 'Files',
            'icon' => 'file',
            'singleton' => false,
            'schema' => [
                [
                    'name' => 'doc',
                    'type' => 'file',
                    'multiple' => false,
                    'rules' => '',
                ],
                [
                    'name' => 'public_user',
                    'type' => 'relation',
                    'collection' => -1,
                    'multiple' => false,
                    'rules' => '',
                ]
            ],
        ]);

        $this->endpoint = EndPoint::create([
            'collection_id' => $this->collection->id,
            'type' => 'update',
            'path' => 'update-file',
            'role' => ['user'],
            'fields' => ['doc', 'public_user'],
            'own_only' => false,
            'trigger_event' => true,
        ]);
    }

    public function test_event_is_triggered_on_update()
    {
        $entryId = DB::table('collection_entries')->insertGetId([
            'collection_id' => $this->collection->id,
            'user_id' => $this->user->id,
            'data' => json_encode(['doc' => null]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->postJson("/api/collections/{$this->collection->name}/{$this->endpoint->path}", [
            'ids' => [$entryId],
            'data' => [
                $entryId => ['doc' => $file],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['updated' => 1, 'success' => true]);

        Event::assertDispatched(CollectionChangedEvent::class);
    }

    public function test_forbidden_fields_are_rejected()
    {
        $entryId = DB::table('collection_entries')->insertGetId([
            'collection_id' => $this->collection->id,
            'user_id' => $this->user->id,
            'data' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/collections/{$this->collection->name}/{$this->endpoint->path}", [
            'ids' => [$entryId],
            'data' => [
                $entryId => ['not_allowed' => 'value'],
            ],
        ]);

        $response->assertStatus(403);
    }

    public function test_file_is_replaced()
    {
        $path = UploadedFile::fake()->create('old.pdf')->store("uploads/{$this->collection->name}", 'local');

        $entryId = DB::table('collection_entries')->insertGetId([
            'collection_id' => $this->collection->id,
            'user_id' => $this->user->id,
            'data' => json_encode(['doc' => $path]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newFile = UploadedFile::fake()->create('new.pdf', 123);

        $response = $this->post("/api/collections/{$this->collection->name}/{$this->endpoint->path}", [
            'ids' => [$entryId],
            'data' => [
                $entryId => ['doc' => $newFile],
            ],
        ]);

        $response->assertStatus(200);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_relation_to_public_user_can_be_set()
    {
        $publicUser = User::factory()->create(['is_public' => true]);

        $entryId = DB::table('collection_entries')->insertGetId([
            'collection_id' => $this->collection->id,
            'user_id' => $this->user->id,
            'data' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/collections/{$this->collection->name}/{$this->endpoint->path}", [
            'ids' => [$entryId],
            'data' => [
                $entryId => ['public_user' => $publicUser->id],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_missing_ids_or_data_returns_error()
    {
        $response = $this->postJson("/api/collections/{$this->collection->name}/{$this->endpoint->path}", [
            'ids' => [],
            'data' => [],
        ]);

        $response->assertStatus(400);
    }
}
