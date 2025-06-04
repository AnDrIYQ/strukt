<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\EndPoint;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EndPointController extends Controller
{
    public function index(Request $request, Collection $collection)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = $collection->endpoints();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('path', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhereJsonContains('role', $search);
            });
        }

        return Inertia::render('EndPoints/Index', [
            'collection' => $collection,
            'endpoints' => $query->paginate($perPage)->withQueryString(),
        ]);
    }
    public function create(Collection $collection)
    {
        return Inertia::render('EndPoints/Create', [
            'collection' => $collection,
            'fields' => collect($collection->schema)->pluck('title', 'name'),
        ]);
    }
    public function store(Request $request, Collection $collection)
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:end_points,path'],
            'type' => ['required', 'in:create,read,update,delete,search'],
            'role' => ['required', 'array'],
            'role.*' => ['in:public,user,admin'],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string'],
            'own_only' => ['boolean'],
            'trigger_event' => ['boolean'],
        ], [
            'path.required' => 'Шлях запита обовʼязковий',
            'path.string' => 'Шлях повинен бути текстовим',
            'path.max' => 'Шлях не може перевищувати 255 символів',
            'path.alpha_dash' => 'Шлях може містити лише літери, цифри, дефіси та підкреслення',
            'path.unique' => 'Такий шлях вже існує',

            'type.required' => 'Тип операції обовʼязковий',
            'type.in' => 'Тип операції повинен бути одним з: Створення, Читання, Оновлення, Видалення, Пошук',

            'role.required' => 'Потрібно вказати ролі доступу',
            'role.array' => 'Ролі повинні бути у форматі списку',
            'role.*.in' => 'Роль повинна бути однією з: Публічний, Користувач або Адміністратор',

            'fields.array' => 'Список полів має бути масивом',
            'fields.*.string' => 'Кожне поле має бути рядком',

            'own_only.boolean' => 'Значення "Доступ лише до власних записів" має бути Так або Ні',
            'trigger_event.boolean' => 'Значення "Створювати веб-сокет подію" має бути Так або Ні',
        ]);

        $collection->endpoints()->create($validated);

        return redirect()
            ->route('collections.endpoints.index', $collection)
            ->with('success', 'Запит створено');
    }
    public function edit(Collection $collection, string $id)
    {
        $endpoint = EndPoint::where('collection_id', $collection->id)
            ->findOrFail($id);

        return Inertia::render('EndPoints/Edit', [
            'collection' => $collection,
            'fields' => collect($collection->schema)
                ->mapWithKeys(fn ($f) => [$f['name'] => $f['title']])
                ->toArray(),
            'endpoint' => [
                'id' => $endpoint->id,
                'path' => $endpoint->path,
                'type' => $endpoint->type,
                'role' => $endpoint->role,
                'fields' => $endpoint->fields ?? [],
                'own_only' => $endpoint->own_only,
                'trigger_event' => $endpoint->trigger_event,
            ],
        ]);
    }
    public function update(Request $request, Collection $collection, string $id)
    {
        $endpoint = EndPoint::where('collection_id', $collection->id)->findOrFail($id);

        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255', Rule::unique('end_points', 'path')->ignore($endpoint->id)],
            'type' => ['required', 'in:create,read,update,delete,search'],
            'role' => ['required', 'array'],
            'role.*' => ['in:public,user,admin'],
            'fields' => ['nullable', 'array'],
            'fields.*' => ['string'],
            'own_only' => ['boolean'],
            'trigger_event' => ['boolean'],
        ], [
            'path.required' => 'Поле шляху обовʼязкове',
            'path.unique' => 'Цей шлях вже використовується',
            'type.required' => 'Поле типу обовʼязкове',
            'type.in' => 'Невірне значення типу',
            'role.required' => 'Вкажіть хоча б одну роль',
            'role.*.in' => 'Недопустима роль',
            'fields.*.string' => 'Назви полів мають бути рядками',
        ]);

        $endpoint->update([
            'path' => $validated['path'],
            'type' => $validated['type'],
            'role' => $validated['role'],
            'fields' => $validated['fields'] ?? null,
            'own_only' => $validated['own_only'] ?? false,
            'trigger_event' => $validated['trigger_event'] ?? false,
        ]);

        return redirect()
            ->route('collections.endpoints.index', $collection)
            ->with('success', 'Запит успішно оновлено.');
    }
    public function destroyMany(Request $request, Collection $collection)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:end_points,id'],
        ]);

        EndPoint::where('collection_id', $collection->id)
            ->whereIn('id', $validated['ids'])
            ->delete();

        return redirect()
            ->route('collections.endpoints.index', $collection)
            ->with('success', 'Вибрані ендпоінти успішно видалено.');
    }
    public function destroy(string $collection, string $id)
    {
        $endpoint = EndPoint::findOrFail($id);

        $endpoint->delete();

        return redirect()
            ->route('collections.endpoints.index', ['collection' => $collection])
            ->with('success', 'Ендпоінт успішно видалено.');
    }
}
