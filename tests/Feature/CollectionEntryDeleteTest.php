<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\EndPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CollectionEntryDeleteTest extends TestCase
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

        $this->collection = Collection::create([
            'name' => 'articles',
            'label' => 'Articles',
            'icon' => 'file-text',
            'singleton' => false,
            'schema' => [
                [
                    'name' => 'title',
                    'title' => 'Title',
                    'type' => 'value',
                    'multiple' => false,
                    'rules' => 'required',
                ],
                [
                    'name' => 'file',
                    'title' => 'File',
                    'type' => 'file',
                    'multiple' => false,
                    'rules' => 'nullable|file',
                ]
            ]
        ]);

        $this->endpoint = EndPoint::create([
            'collection_id' => $this->collection->id,
            'path' => 'delete-entry',
            'type' => 'delete',
            'role' => ['user'],
            'own_only' => false,
            'trigger_event' => false,
        ]);
    }

    public function test_user_can_delete_entry()
    {
        $this->actingAs($this->user, 'sanctum');

        $entryId = DB::table('collection_entries')->insertGetId([
            'collection_id' => $this->collection->id,
            'user_id' => $this->user->id,
            'data' => json_encode(['title' => 'To delete']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/collections/{$this->collection->name}/delete-entry", [
            'ids' => [$entryId],
        ]);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'deleted' => 1,
        ]);

        $this->assertDatabaseMissing('collection_entries', [
            'id' => $entryId,
        ]);
    }

    public function test_user_cannot_delete_foreign_entry_when_own_only()
    {
        $this->endpoint->update(['own_only' => true]);

        $this->actingAs($this->user, 'sanctum');

        $otherUser = User::factory()->create();
        $entryId = DB::table('collection_entries')->insertGetId([
            'collection_id' => $this->collection->id,
            'user_id' => $otherUser->id,
            'data' => json_encode(['title' => 'Not yours']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson("/api/collections/{$this->collection->name}/delete-entry", [
            'ids' => [$entryId],
        ]);

        $response->assertStatus(404);
        $this->assertDatabaseHas('collection_entries', ['id' => $entryId]);
    }

    public function test_file_is_deleted_with_entry()
    {
        $this->actingAs($this->user, 'sanctum');

        $fakeFile = UploadedFile::fake()->create('doc.pdf');
        $path = $fakeFile->store("uploads/{$this->collection->name}", 'local');

        $entryId = DB::table('collection_entries')->insertGetId([
            'collection_id' => $this->collection->id,
            'user_id' => $this->user->id,
            'data' => json_encode(['title' => 'With file', 'file' => $path]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Storage::disk('local')->assertExists($path);

        $response = $this->postJson("/api/collections/{$this->collection->name}/delete-entry", [
            'ids' => [$entryId],
        ]);

        $response->assertStatus(200);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_deleting_multiple_entries()
    {
        $this->actingAs($this->user, 'sanctum');

        $ids = collect([1, 2, 3])->map(function ($i) {
            return DB::table('collection_entries')->insertGetId([
                'collection_id' => $this->collection->id,
                'user_id' => $this->user->id,
                'data' => json_encode(['title' => "Item {$i}"]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        })->toArray();

        $response = $this->postJson("/api/collections/{$this->collection->name}/delete-entry", [
            'ids' => $ids,
        ]);

        $response->assertStatus(200)
            ->assertJson(['deleted' => 3]);

        foreach ($ids as $id) {
            $this->assertDatabaseMissing('collection_entries', ['id' => $id]);
        }
    }

    public function test_delete_requires_ids()
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->postJson("/api/collections/{$this->collection->name}/delete-entry", []);

        $response->assertStatus(400);
    }
}
