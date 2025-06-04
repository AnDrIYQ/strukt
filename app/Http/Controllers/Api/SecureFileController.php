<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SecureFileController extends Controller
{
    public function show(Request $request, string $collectionName, string $field, int $entryId, ?int $index = null)
    {
        $collection = Collection::where('name', $collectionName)->firstOrFail();
        $schema = collect($collection->schema)->keyBy('name');

        $fieldSchema = $schema[$field] ?? null;
        if (!$fieldSchema || $fieldSchema['type'] !== 'file') {
            abort(404, translate('Поле не знайдено або не є файлом'));
        }

        $entry = DB::table('collection_entries')
            ->where('collection_id', $collection->id)
            ->where('id', $entryId)
            ->firstOrFail();

        $data = json_decode($entry->data, true);
        $isMultiple = $fieldSchema['multiple'] ?? false;

        if (!array_key_exists($field, $data)) {
            abort(404, translate('Файл не знайдено в записі'));
        }

        if ($isMultiple) {
            $files = is_array($data[$field]) ? $data[$field] : [];
            $filePath = $files[$index] ?? null;
        } else {
            $filePath = $data[$field];
        }

        if (!$filePath || !Storage::disk('local')->exists($filePath)) {
            abort(404, translate('Файл не знайдено'));
        }

        return Storage::disk('local')->response($filePath);
    }
}
