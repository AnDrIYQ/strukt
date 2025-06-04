<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = Collection::query()
            ->select('id', 'name', 'label', 'icon', 'schema');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('label', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Collections/Index', [
            'collections' => $query->paginate($perPage)->withQueryString(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Collections/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:collections,name'],
            'label' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'singleton' => ['required', 'boolean'],
            'schema' => ['required', 'array', 'min:1'],
            'schema.*.title' => ['required', 'string', 'max:255'],
            'schema.*.name' => ['required', 'string', 'alpha_dash', 'max:255'],
            'schema.*.type' => ['required', 'in:file,value,relation'],
            'schema.*.multiple' => ['boolean'],
            'schema.*.rules' => ['nullable', 'string', 'max:1000'],
            'schema.*.collection' => [
                'required_if:schema.*.type,relation',
                function ($attribute, $value, $fail) {
                    if (!is_string($value) && $value !== -1) {
                        $fail('Поле collection має бути рядком (назва колекції) або -1 для посилання на публічних користувачів.');
                    }
                }
            ],
        ], [
            'name.required' => 'Ідентифікатор обов\'язковий',
            'name.max' => 'Ідентифікатор не повинен перевищувати 255 символів',
            'name.unique' => 'Такий ідентифікатор вже існує',
            'name.alpha_dash' => 'Ідентифікатор має містити лише літери, цифри, дефіси або підкреслення.',

            'label.required' => 'Назва обов\'язкова',
            'label.max' => 'Назва не повинна перевищувати 255 символів',

            'icon.max' => 'Назва іконки не повинна перевищувати 255 символів',

            'singleton.required' => 'Вкажіть, чи це одинична колекція',
            'singleton.boolean' => 'Поле singleton повинно бути true або false',

            'schema.required' => 'Схема колекції повинна містити хоча б одне поле',
            'schema.min' => 'Схема колекції повинна містити хоча б одне поле',
            'schema.*.title.required' => 'Введіть заголовок',
            'schema.*.name.required' => 'Введіть назву поля',
            'schema.*.name.alpha_dash' => 'Може містити лише літери, цифри, дефіс або підкреслення',
            'schema.*.type.required' => 'Оберіть тип',
            'schema.*.type.in' => 'Неприпустиме значення типу',
            'schema.*.multiple.boolean' => 'Поле "Багатозначне" має бути булевим',
            'schema.*.collection.required_if' => 'Оберіть колекцію для посилання',
        ]);

        Collection::create($validated);

        return redirect()->route('collections.index')->with('success', 'Колекція створена');
    }

    public function destroyMany(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:collections,id'],
        ]);

        Collection::whereIn('id', $validated['ids'])->delete();

        return redirect()
            ->route('collections.index')
            ->with('success', 'Обрані колекції успішно видалено.');
    }

    public function destroy(Collection $collection)
    {
        $collection->delete();

        return redirect()->back()->with('success', 'Колекцію видалено');
    }
}
