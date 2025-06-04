<?php

namespace App\Http\Controllers\Api;

use App\Events\CollectionChangedEvent;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Collection;
use App\Models\EndPoint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EndPointExecutorController extends Controller
{
    public function publicUsers(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = \App\Models\User::query()
            ->where('is_public', true)
            ->select('id', 'name', 'avatar', 'email');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $results = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        $transformed = $results->getCollection()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar
                    ? route('avatar.public', ['user' => $user->id], false)
                    : null,
            ];
        });

        return response()->json([
            'data' => $transformed,
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ]
        ]);
    }

    public function handle(Request $request, string $collection, string $path)
    {
        $collectionModel = Collection::where('name', $collection)->firstOrFail();

        $endpoint = EndPoint::where('collection_id', $collectionModel->id)
            ->where('path', $path)
            ->first();

        if (!$endpoint) {
            throw new NotFoundHttpException(translate('Ендпоінт не знайдено'));
        }

        return match ($endpoint->type) {
            'read'    => $this->handleRead($request, $collectionModel, $endpoint),
            'create'  => $this->handleCreate($request, $collectionModel, $endpoint),
            'update'  => $this->handleUpdate($request, $collectionModel, $endpoint),
            'delete'  => $this->handleDelete($request, $collectionModel, $endpoint),
            'search'  => $this->handleSearch($request, $collectionModel, $endpoint),
            default   => throw new HttpException(400, translate('Невідомий тип запиту')),
        };
    }

    protected function handleRead(Request $request, Collection $collection, EndPoint $endpoint)
    {
        $user = Auth::user();
        $this->checkAccess($endpoint, $user);

        if ($collection->singleton) {
            $entry = DB::table('collection_entries')
                ->where('collection_id', $collection->id)
                ->when($endpoint->own_only, fn($q) => $q->where('user_id', $user?->id))
                ->orderByDesc('id')
                ->first();

            if (!$entry) {
                return response()->json(null);
            }

            $populateFields = collect(explode(',', $request->input('populate', '')))
                ->filter()
                ->values();

            $relationSchemas = collect($collection->schema)
                ->filter(fn($field) => in_array($field['name'], $populateFields->all()) && $field['type'] === 'relation')
                ->keyBy('name');

            return response()->json(
                $this->transformEntry($entry, $collection, $endpoint, $relationSchemas)
            );
        }

        $perPage = $request->input('per_page', 10);

        $query = DB::table('collection_entries')
            ->where('collection_id', $collection->id);

        if ($endpoint->own_only) {
            if (!$user) {
                throw new HttpException(401, translate('Потрібна авторизація для доступу до власних записів'));
            }

            $query->where('user_id', $user->id);
        }

        if ($search = $request->input('search')) {
            $query->where('data', 'like', "%{$search}%");
        }

        $results = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        $populateFields = collect(explode(',', $request->input('populate', '')))
            ->filter()
            ->values();

        $relationSchemas = collect($collection->schema)
            ->filter(fn($field) => in_array($field['name'], $populateFields->all()) && $field['type'] === 'relation')
            ->keyBy('name');

        $transformed = $results->through(
            fn($item) => $this->transformEntry($item, $collection, $endpoint, $relationSchemas)
        );

        return response()->json([
            'data' => $transformed,
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ]
        ]);
    }

    protected function handleFiles(Request $request, array &$validated, Collection $collection, ?int $index = null): void
    {
        foreach ($collection->schema as $field) {
            $name = $field['name'];
            $type = $field['type'];
            $multiple = $field['multiple'] ?? false;

            if ($type !== 'file' || !isset($validated[$name])) {
                continue;
            }

            $fieldKey = is_null($index) ? $name : "{$index}.{$name}";

            if ($multiple) {
                $validated[$name] = collect($validated[$name] ?? [])
                    ->map(function ($original, $subIndex) use ($request, $collection, $fieldKey, $index, $name) {
                        $fileKey = is_null($index)
                            ? "{$name}.{$subIndex}"
                            : "{$index}.{$name}.{$subIndex}";

                        $file = $request->file($fileKey);

                        return $file instanceof \Illuminate\Http\UploadedFile
                            ? $file->store("uploads/{$collection->name}", ['disk' => 'local'])
                            : null;
                    })
                    ->filter()
                    ->values()
                    ->toArray();
            } else {
                $file = $request->file($fieldKey);
                $validated[$name] = $file instanceof \Illuminate\Http\UploadedFile
                    ? $file->store("uploads/{$collection->name}", ['disk' => 'local'])
                    : $validated[$name];
            }
        }
    }

    public function handleCreate(Request $request, Collection $collection, EndPoint $endpoint)
    {
        $user = Auth::user();
        $this->checkAccess($endpoint, $user);

        $input = $request->all();

        if ($collection->singleton) {
            $entry = DB::table('collection_entries')
                ->where('collection_id', $collection->id)
                ->when($endpoint->own_only, fn($q) => $q->where('user_id', $user?->id))
                ->first();

            $validated = $this->validateEntry($collection, $input);
            $this->handleFiles($request, $validated, $collection);

            if ($entry) {
                DB::table('collection_entries')->where('id', $entry->id)->update([
                    'data' => json_encode($validated),
                    'updated_at' => now(),
                ]);

                if ($endpoint->trigger_event) {
                    broadcast(new CollectionChangedEvent(
                        $collection,
                        'update',
                        $endpoint,
                        $user?->id,
                        [['id' => $entry->id, 'data' => $validated]]
                    ));
                }

                return response()->json(['success' => true, 'updated' => true]);
            }

            $newId = DB::table('collection_entries')->insertGetId([
                'collection_id' => $collection->id,
                'user_id' => $user?->id,
                'data' => json_encode($validated),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($endpoint->trigger_event) {
                broadcast(new CollectionChangedEvent(
                    $collection,
                    'create',
                    $endpoint,
                    $user?->id,
                    [['id' => $newId, 'data' => $validated]]
                ));
            }

            return response()->json(['success' => true, 'created' => true]);
        }

        $isArray = isset($input[0]) && is_array($input[0]);
        $entries = $isArray ? $input : [$input];

        $validatedEntries = collect($entries)->map(function ($entry, $index) use ($request, $collection, $isArray) {
            $validated = $this->validateEntry($collection, $entry);
            $this->handleFiles($request, $validated, $collection, $isArray ? $index : null);
            return $validated;
        })->all();

        $created = [];

        foreach ($validatedEntries as $data) {
            $row = [
                'collection_id' => $collection->id,
                'user_id' => $user?->id,
                'data' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('collection_entries')->insert($row);
            $row['data'] = json_decode($row['data']);
            $created[] = $row;
        }

        if ($endpoint->trigger_event) {
            broadcast(new CollectionChangedEvent(
                $collection,
                'create',
                $endpoint,
                $user?->id,
                $created
            ));
        }

        return response()->json(['success' => true, 'created' => count($created)]);
    }

    protected function handleUpdate(Request $request, Collection $collection, EndPoint $endpoint)
    {
        $user = Auth::user();
        $this->checkAccess($endpoint, $user);

        $schema = collect($collection->schema)->keyBy('name');

        if ($collection->singleton) {
            $entry = DB::table('collection_entries')
                ->where('collection_id', $collection->id)
                ->when($endpoint->own_only, fn($q) => $q->where('user_id', $user?->id))
                ->first();

            $newData = $request->all();
            if ($endpoint->fields) {
                $invalid = array_diff(array_keys($newData), $endpoint->fields);
                if ($invalid) {
                    abort(403, translate('Спроба змінити заборонені поля: ') . implode(', ', $invalid));
                }
            }
            $validated = $this->validateEntry($collection, $newData, true);
            if ($endpoint->fields) {
                $validated = Arr::only($validated, $endpoint->fields);
            }
            $processed = $this->processFiles($request, $collection, $validated, $schema);

            if (!$entry) {
                $newId = DB::table('collection_entries')->insertGetId([
                    'collection_id' => $collection->id,
                    'user_id' => $user?->id,
                    'data' => json_encode($processed),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($endpoint->trigger_event) {
                    broadcast(new CollectionChangedEvent(
                        $collection,
                        'create',
                        $endpoint,
                        $user?->id,
                        [['id' => $newId, 'data' => $processed]]
                    ));
                }

                return response()->json(['success' => true, 'created' => true]);
            }

            $this->deleteReplacedFiles(json_decode($entry->data, true), $processed, $schema);

            DB::table('collection_entries')->where('id', $entry->id)->update([
                'data' => json_encode($processed),
                'updated_at' => now(),
            ]);

            if ($endpoint->trigger_event) {
                broadcast(new CollectionChangedEvent(
                    $collection,
                    'update',
                    $endpoint,
                    $user?->id,
                    [['id' => $entry->id, 'data' => $processed]]
                ));
            }

            return response()->json(['success' => true, 'updated' => 1]);
        }

        $ids = $request->all('ids')['ids'];
        $data = $request->all('data')['data'];

        if (empty($ids) || empty($data)) {
            abort(400, translate('Не вказано ID або даних для оновлення'));
        }

        $query = DB::table('collection_entries')
            ->where('collection_id', $collection->id)
            ->whereIn('id', $ids);

        if ($endpoint->own_only) {
            $query->where('user_id', $user->id);
        }

        $entries = $query->get()->keyBy('id');
        if ($entries->isEmpty()) {
            abort(404, translate('Записи не знайдені або доступ заборонено'));
        }
        $missing = array_diff($ids, $entries->keys()->all());
        if (!empty($missing)) {
            abort(404, translate('Деякі записи не знайдено або доступ до них заборонено'));
        }

        $updatedItems = [];

        foreach ($ids as $id) {
            if (!isset($entries[$id]) || !isset($data[$id])) {
                continue;
            }

            $entry = $entries[$id];
            $newData = $data[$id];

            $existing = (array) json_decode($entry->data, true);
            if ($endpoint->fields) {
                $invalid = array_diff(array_keys($newData), $endpoint->fields);
                if ($invalid) {
                    abort(403, translate('Спроба змінити заборонені поля: ') . implode(', ', $invalid));
                }
            }
            $validated = $this->validateEntry($collection, $newData, true);
            if ($endpoint->fields) {
                $validated = Arr::only($validated, $endpoint->fields);
            }
            $merged = array_merge($existing, $validated);

            $processed = $this->processFiles($request, $collection, $merged, $schema, $id);

            $this->deleteReplacedFiles($existing, $processed, $schema);

            DB::table('collection_entries')
                ->where('id', $id)
                ->update([
                    'data' => json_encode($processed),
                    'updated_at' => now(),
                ]);

            $updatedItems[] = [
                'id' => $id,
                'data' => $processed,
            ];
        }

        if ($endpoint->trigger_event && $updatedItems) {
            broadcast(new CollectionChangedEvent(
                $collection,
                'update',
                $endpoint,
                $user?->id,
                $updatedItems
            ));
        }

        return response()->json(['success' => true, 'updated' => count($updatedItems)]);
    }

    protected function handleDelete(Request $request, Collection $collection, EndPoint $endpoint)
    {
        $user = Auth::user();
        $this->checkAccess($endpoint, $user);

        $schema = collect($collection->schema)->keyBy('name');

        if ($collection->singleton) {
            $entry = DB::table('collection_entries')
                ->where('collection_id', $collection->id)
                ->when($endpoint->own_only, fn($q) => $q->where('user_id', $user?->id))
                ->first();

            if (!$entry) {
                return response()->json(['deleted' => 0]);
            }

            $this->deleteFilesFromEntry(json_decode($entry->data, true), $schema);

            DB::table('collection_entries')->where('id', $entry->id)->delete();

            if ($endpoint->trigger_event) {
                broadcast(new CollectionChangedEvent(
                    $collection,
                    'delete',
                    $endpoint,
                    $user?->id,
                    [['id' => $entry->id]]
                ));
            }

            return response()->json(['success' => true, 'deleted' => 1]);
        }

        $ids = (array) $request->input('ids', $request->input('id'));
        if (empty($ids)) {
            abort(400, translate('Не вказано ID запису(ів) для видалення'));
        }

        $query = DB::table('collection_entries')
            ->where('collection_id', $collection->id)
            ->whereIn('id', $ids);

        if ($endpoint->own_only) {
            $query->where('user_id', $user->id);
        }

        $entries = $query->get();
        if ($entries->isEmpty()) {
            abort(404, translate('Жоден із записів не знайдено або доступ заборонено'));
        }

        foreach ($entries as $entry) {
            $this->deleteFilesFromEntry(json_decode($entry->data, true), $schema);
        }

        $deletedIds = $entries->pluck('id')->toArray();

        DB::table('collection_entries')
            ->whereIn('id', $deletedIds)
            ->delete();

        if ($endpoint->trigger_event) {
            broadcast(new CollectionChangedEvent(
                $collection,
                'delete',
                $endpoint,
                $user?->id,
                collect($deletedIds)->map(fn($id) => ['id' => $id])->toArray()
            ));
        }

        return response()->json(['success' => true, 'deleted' => count($deletedIds)]);
    }

    public function checkAccess(EndPoint $endpoint, User $user = null, $record = null): bool
    {
        $roles = $endpoint->role;

        if (in_array('public', $roles)) {
            return true;
        }

        if (!$user) {
            throw new HttpException(401, 'Потрібна авторизація');
        }

        if (!in_array($user->role, $roles)) {
            throw new HttpException(403, 'Недостатньо прав доступу');
        }

        if ($endpoint->own_only && $record) {
            if ($record->user_id !== $user->id) {
                throw new HttpException(403, 'Доступ лише до власних записів');
            }
        }

        return true;
    }

    protected function transformEntry($item, Collection $collection, EndPoint $endpoint, ?\Illuminate\Support\Collection $relationSchemas = null): array
    {
        $data = collect(json_decode($item->data, true))
            ->only($endpoint->fields ?? [])
            ->toArray();

        $schema = collect($collection->schema)->keyBy('name');

        foreach ($schema as $field) {
            if (($field['type'] ?? null) !== 'file') continue;

            $name = $field['name'];
            $isMultiple = $field['multiple'] ?? false;

            if (!array_key_exists($name, $data)) continue;

            if ($isMultiple && is_array($data[$name])) {
                $data[$name] = collect($data[$name])
                    ->map(function ($path, $index) use ($collection, $name, $item) {
                        return $path
                            ? URL::signedRoute('secure-file', [
                                'collection' => $collection->name,
                                'field' => $name,
                                'entry' => $item->id,
                                'index' => $index,
                            ])
                            : null;
                    })
                    ->filter()
                    ->values()
                    ->toArray();
            } else {
                $path = $data[$name];
                $data[$name] = $path
                    ? URL::signedRoute('secure-file', [
                        'collection' => $collection->name,
                        'field' => $name,
                        'entry' => $item->id,
                    ])
                    : null;
            }
        }

        if ($relationSchemas) {
            foreach ($relationSchemas as $fieldName => $fieldSchema) {
                $isMultiple = $fieldSchema['multiple'] ?? false;
                $relatedIds = $isMultiple
                    ? ($data[$fieldName] ?? [])
                    : [($data[$fieldName] ?? null)];

                $relatedIds = array_filter($relatedIds);

                if (empty($relatedIds)) {
                    $data[$fieldName] = $isMultiple ? [] : null;
                    continue;
                }

                if ($fieldSchema['collection'] === -1) {
                    $relatedEntries = \App\Models\User::whereIn('id', $relatedIds)
                        ->where('is_public', true)
                        ->get()
                        ->keyBy('id')
                        ->map(fn($u) => [
                            'id' => $u->id,
                            'name' => $u->name,
                            'email' => $u->email,
                            'avatar' => $u->avatar ? route('avatar.public', ['user' => $u->id], false) : null,
                        ]);
                } else {
                    $relatedEntries = DB::table('collection_entries')
                        ->join('collections', 'collections.id', '=', 'collection_entries.collection_id')
                        ->where('collections.name', $fieldSchema['collection'])
                        ->whereIn('collection_entries.id', $relatedIds)
                        ->select('collection_entries.*')
                        ->get()
                        ->map(fn($e) => ['id' => $e->id] + json_decode($e->data, true))
                        ->keyBy('id');
                }

                $data[$fieldName] = $isMultiple
                    ? collect($relatedIds)->map(fn($id) => $relatedEntries[$id] ?? null)->filter()->values()->toArray()
                    : ($relatedEntries[$relatedIds[0]] ?? null);
            }
        }

        return array_merge(['id' => $item->id], $data);
    }

    protected function handleSearch(Request $request, Collection $collection, EndPoint $endpoint)
    {
        $user = Auth::user();
        $this->checkAccess($endpoint, $user);

        $perPage = $request->input('per_page', 10);
        $filters = $request->input('filters', []);

        $query = DB::table('collection_entries')
            ->where('collection_id', $collection->id);

        if ($endpoint->own_only) {
            if (!$user) {
                throw new HttpException(401, 'Потрібна авторизація для доступу до власних записів');
            }

            $query->where('user_id', $user->id);
        }

        $schema = collect($collection->schema)->keyBy('name');

        foreach ($filters as $field => $value) {
            logger()->debug('FILTER LOOP', compact('field', 'value'));

            if (is_array($value)) continue;
            if (is_null($value)) continue;

            if ($field === 'id' && is_numeric($value)) {
                logger()->debug('Applying ID filter', ['id' => $value]);
                $query->where('id', $value);
                continue;
            }

            $fieldSchema = collect($collection->schema)->firstWhere('name', $field);

            if ($fieldSchema && $fieldSchema['type'] === 'relation') {
                $relatedCollection = $fieldSchema['collection'];
                $multiple = $fieldSchema['multiple'] ?? false;

                logger()->debug('Relation field detected', [
                    'value' => $value,
                    'field' => $field,
                    'multiple' => $multiple,
                    'relatedCollection' => $relatedCollection
                ]);

                if ((int)$relatedCollection === -1) {
                    $query->whereExists(function ($q) use ($value, $field, $multiple) {
                        $q->select(DB::raw(1))
                            ->from('users')
                            ->where(function ($sub) use ($value, $field, $multiple) {
                                if ($multiple) {
                                    $sub->whereRaw("JSON_CONTAINS(data->'$.relatio_mp', JSON_QUOTE(CAST(users.id AS CHAR)))");
                                } else {
                                    $sub->whereRaw("
                                users.id = json_unquote(json_extract(collection_entries.data, '$.\"{$field}\"'))
                            ");
                                }

                                $sub->where(function ($search) use ($value) {
                                    $search->where('users.name', 'like', "%{$value}%")
                                        ->orWhere('users.email', 'like', "%{$value}%");
                                });
                            });

                        logger()->debug('RAW SQL for user relation', [
                            'value' => $value,
                            'field' => $field,
                            'multiple' => $multiple,
                        ]);
                    });
                } else {
                    $query->whereExists(function ($q) use ($value, $field, $relatedCollection, $multiple) {
                        $q->select(DB::raw(1))
                            ->from('collection_entries as related')
                            ->where('related.collection_id', $relatedCollection)
                            ->where(function ($sub) use ($value, $field, $multiple) {
                                if ($multiple) {
                                    $sub->whereRaw("
                                JSON_CONTAINS(
                                    json_extract(collection_entries.data, '$.\"{$field}\"'),
                                    CAST(related.id AS JSON)
                                )
                            ");
                                } else {
                                    $sub->whereRaw("
                                related.id = json_unquote(json_extract(collection_entries.data, '$.\"{$field}\"'))
                            ");
                                }

                                $sub->where('related.data', 'like', "%{$value}%");
                            });

                        logger()->debug('RAW SQL for related collection relation', [
                            'value' => $value,
                            'field' => $field,
                            'multiple' => $multiple,
                            'collection_id' => $relatedCollection
                        ]);
                    });
                }
                continue;
            }
            $query->where("data->{$field}", 'like', "%{$value}%");
        }

        $results = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        $populateFields = collect(explode(',', $request->input('populate', '')))
            ->filter()
            ->values();

        $relationSchemas = collect($collection->schema)
            ->filter(fn($field) => in_array($field['name'], $populateFields->all()) && $field['type'] === 'relation')
            ->keyBy('name');

        $transformed = $results->through(
            fn($item) => $this->transformEntry($item, $collection, $endpoint, $relationSchemas)
        );

        return response()->json([
            'data' => $transformed,
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ]
        ]);
    }

    protected function processFiles(Request $request, Collection $collection, array $validated, $schema, $entryId = null)
    {
        foreach ($validated as $key => &$value) {
            $field = $schema[$key] ?? null;
            if (!$field || $field['type'] !== 'file') continue;

            $multiple = $field['multiple'] ?? false;

            if ($multiple) {
                $value = collect($value)->map(function ($original, $index) use ($request, $key, $collection, $entryId) {
                    $file = Arr::get($request->allFiles(), "data.{$entryId}.{$key}.{$index}");
                    return $file instanceof \Illuminate\Http\UploadedFile
                        ? $file->store("uploads/{$collection->name}", ['disk' => 'local'])
                        : $original;
                })->filter()->values()->toArray();
            } else {
                $file = Arr::get($request->allFiles(), "data.{$entryId}.{$key}");
                $value = $file instanceof \Illuminate\Http\UploadedFile
                    ? $file->store("uploads/{$collection->name}", ['disk' => 'local'])
                    : $value;
            }
        }

        return $validated;
    }

    protected function deleteReplacedFiles(array $old, array $new, $schema)
    {
        foreach ($schema as $key => $field) {
            if ($field['type'] !== 'file') continue;

            $multiple = $field['multiple'] ?? false;
            $oldPaths = $old[$key] ?? [];
            $newPaths = $new[$key] ?? [];

            $oldPaths = (array) $oldPaths;
            $newPaths = (array) $newPaths;

            foreach ($oldPaths as $path) {
                if ($path && !in_array($path, $newPaths)) {
                    if (Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->delete($path);
                    }
                }
            }
        }
    }

    protected function deleteFilesFromEntry(array $data, $schema)
    {
        foreach ($schema as $field) {
            if ($field['type'] !== 'file') continue;

            $name = $field['name'];
            $multiple = $field['multiple'] ?? false;

            $paths = $data[$name] ?? null;
            if (!$paths) continue;

            if ($multiple && is_array($paths)) {
                Storage::disk('local')->delete($paths);
            } elseif (is_string($paths)) {
                Storage::disk('local')->delete($paths);
            }
        }
    }

    protected function validateEntry(Collection $collection, array $entryData, bool $partial = false): array
    {
        $rules = [];

        foreach ($collection->schema as $field) {
            $name = $field['name'];
            $type = $field['type'];
            $isMultiple = $field['multiple'] ?? false;
            $baseRules = $field['rules'] ?? '';
            $fieldRules = [];

            if ($partial && !array_key_exists($name, $entryData)) {
                continue;
            }

            if ($type === 'file') {
                $fieldRules[] = 'file';
            }

            if ($type === 'relation') {
                $fieldRules[] = 'integer';
                if ($field['collection'] === -1) {
                    $fieldRules[] = 'exists:users,id,is_public,1';
                } else {
                    $fieldRules[] = 'exists:collection_entries,id';
                }
            }

            if ($baseRules) {
                $fieldRules = array_merge(
                    $fieldRules,
                    is_string($baseRules) ? explode('|', $baseRules) : (array) $baseRules
                );
            }

            if ($isMultiple) {
                $rules[$name] = ['array'];
                $rules["{$name}.*"] = $fieldRules;
            } else {
                $rules[$name] = $fieldRules;
            }
        }

        return validator($entryData, $rules)->validate();
    }
}
