<?php

namespace Tests\Feature;

use App\Events\CollectionChangedEvent;
use App\Models\Collection;
use App\Models\EndPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CollectionEntryCreateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Collection $collection;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Event::fake();

        $this->user = User::factory()->create(['role' => 'user']);

        $this->actingAs($this->user, 'sanctum');

        $this->collection = Collection::create([
            'name' => 'posts',
            'label' => 'Posts',
            'icon' => 'file-text',
            'singleton' => false,
            'schema' => [
                ['name' => 'title', 'title' => 'Title', 'type' => 'value', 'multiple' => false, 'rules' => 'required|string'],
                ['name' => 'image', 'title' => 'Image', 'type' => 'file', 'multiple' => false, 'rules' => 'nullable'],
                ['name' => 'attachments', 'title' => 'Attachments', 'type' => 'file', 'multiple' => true, 'rules' => 'nullable'],
                ['name' => 'related', 'title' => 'Related', 'type' => 'relation', 'multiple' => true, 'collection' => 'posts', 'rules' => 'nullable'],
                ['name' => 'user_ref', 'title' => 'User', 'type' => 'relation', 'multiple' => false, 'collection' => -1, 'rules' => 'nullable'],
            ]
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_with_invalid_role_cannot_create()
    {
        $endpoint = EndPoint::create([
            'path' => 'create-entry',
            'collection_id' => $this->collection->id,
            'type' => 'create',
            'role' => ['admin'],
        ]);

        $response = $this->postJson("/api/collections/{$this->collection->name}/{$endpoint->path}", [
            'title' => 'Заборонений запис',
        ]);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function trigger_event_dispatches_broadcast()
    {
        $endpoint = EndPoint::create([
            'path' => 'create-entry',
            'collection_id' => $this->collection->id,
            'type' => 'create',
            'role' => ['user'],
            'trigger_event' => true,
        ]);

        $response = $this->postJson("/api/collections/{$this->collection->name}/{$endpoint->path}", [
            'title' => 'З івентом',
        ]);

        $response->assertStatus(200);
        Event::assertDispatched(CollectionChangedEvent::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function only_own_entry_is_created_with_user_id()
    {
        $endpoint = EndPoint::create([
            'path' => 'create-entry',
            'collection_id' => $this->collection->id,
            'type' => 'create',
            'role' => ['user'],
            'own_only' => true,
        ]);

        $this->postJson("/api/collections/{$this->collection->name}/{$endpoint->path}", [
            'title' => 'Only own',
        ])->assertStatus(200);

        $this->assertDatabaseHas('collection_entries', [
            'user_id' => $this->user->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function file_and_multiple_file_are_saved()
    {
        $endpoint = EndPoint::create([
            'path' => 'create-entry',
            'collection_id' => $this->collection->id,
            'type' => 'create',
            'role' => ['user'],
        ]);

        $response = $this->postJson("/api/collections/{$this->collection->name}/{$endpoint->path}", [
            'title' => 'Файл',
            'image' => UploadedFile::fake()->image('img.jpg'),
            'attachments' => [
                UploadedFile::fake()->create('doc1.pdf'),
                UploadedFile::fake()->create('doc2.pdf'),
            ]
        ]);

        $response->assertStatus(200);
        Storage::disk('local')->assertExists('uploads/posts');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function relation_to_public_user_is_allowed()
    {
        $relatedUser = User::factory()->create([
            'is_public' => true,
        ]);

        $endpoint = EndPoint::create([
            'path' => 'create-entry',
            'collection_id' => $this->collection->id,
            'type' => 'create',
            'role' => ['user'],
        ]);

        $this->postJson("/api/collections/{$this->collection->name}/{$endpoint->path}", [
            'title' => 'З посиланням',
            'user_ref' => $relatedUser->id,
        ])->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_can_create_multiple_entries()
    {
        $endpoint = EndPoint::create([
            'path' => 'create-mass',
            'collection_id' => $this->collection->id,
            'type' => 'create',
            'role' => ['user'],
        ]);

        $response = $this->postJson("/api/collections/{$this->collection->name}/{$endpoint->path}", [
            ['title' => 'Перший'],
            ['title' => 'Другий'],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('collection_entries', 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_create_entry_without_required_fields(): void
    {
        $collection = Collection::create([
            'name' => 'tasks',
            'label' => 'Tasks',
            'icon' => 'list',
            'singleton' => false,
            'schema' => [
                [
                    'name' => 'title',
                    'title' => 'Заголовок',
                    'type' => 'value',
                    'multiple' => false,
                    'rules' => 'required|string|max:255',
                ],
            ]
        ]);

        $endpoint = EndPoint::create([
            'path' => 'create-task',
            'collection_id' => $collection->id,
            'type' => 'create',
            'role' => ['user'],
            'fields' => null,
            'own_only' => false,
            'trigger_event' => false,
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/collections/{$collection->name}/{$endpoint->path}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function user_with_invalid_role_cannot_create_entry(): void
    {
        $collection = Collection::create([
            'name' => 'reports',
            'label' => 'Reports',
            'icon' => 'bar-chart',
            'singleton' => false,
            'schema' => [
                [
                    'name' => 'summary',
                    'title' => 'Summary',
                    'type' => 'value',
                    'multiple' => false,
                    'rules' => 'required|string',
                ],
            ]
        ]);

        $endpoint = EndPoint::create([
            'path' => 'create-report',
            'collection_id' => $collection->id,
            'type' => 'create',
            'role' => ['admin'],
            'fields' => null,
            'own_only' => false,
            'trigger_event' => false,
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/collections/{$collection->name}/{$endpoint->path}", [
            'summary' => 'Test report',
        ]);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function cannot_create_entry_with_invalid_file_type(): void
    {
        $collection = Collection::create([
            'name' => 'documents',
            'label' => 'Documents',
            'icon' => 'file',
            'singleton' => false,
            'schema' => [
                [
                    'name' => 'attachment',
                    'title' => 'Файл',
                    'type' => 'file',
                    'multiple' => false,
                    'rules' => 'required',
                ],
            ]
        ]);

        $endpoint = EndPoint::create([
            'path' => 'create-document',
            'collection_id' => $collection->id,
            'type' => 'create',
            'role' => ['user'],
            'fields' => null,
            'own_only' => false,
            'trigger_event' => false,
        ]);

        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user, 'sanctum');

        $response = $this->postJson("/api/collections/{$collection->name}/{$endpoint->path}", [
            'attachment' => 'not-a-real-file.pdf',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attachment']);
    }
}
