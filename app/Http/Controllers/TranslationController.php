<?php

namespace App\Http\Controllers;

use App\Models\Translation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TranslationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');

        $query = Translation::query();

        if ($search) {
            $query->where('key', 'like', "%$search%")
                ->orWhere('message', 'like', "%$search%");
        }

        return Inertia::render('Translations/Index', [
            'translations' => $query->orderBy('key')->paginate($perPage)->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:255', 'unique:translations,key'],
            'message' => ['nullable', 'string'],
        ], [
            'key.unique' => 'Переклад вже існує',
            'key.max' => 'Ключ повідомлення не повинен перевищувати 255 символів',
            'key.required' => 'Введіть ключ',
        ]);

        Translation::create($validated);

        return redirect()
            ->route('translations.index')
            ->with('success', 'Переклад додано');
    }

    public function update(Request $request, Translation $translation)
    {
        $validated = $request->validate([
            'message' => ['nullable', 'string'],
        ]);

        $translation->update($validated);

        return redirect()
            ->route('translations.index')
            ->with('success', 'Переклад оновлено');
    }
    public function destroy(Translation $translation)
    {
        $translation->delete();

        return redirect()
            ->route('translations.index')
            ->with('success', 'Переклад видалено');
    }
}
