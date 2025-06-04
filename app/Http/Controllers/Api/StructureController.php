<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StructureController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = $user?->role ?? 'public';

        $collections = Collection::with('endpoints')->get()->map(function ($collection) use ($role) {
            $endpoints = $collection->endpoints->filter(function ($endpoint) use ($role) {
                return in_array($role, $endpoint->role ?? []);
            })->values();

            if ($endpoints->isEmpty()) {
                return null;
            }

            $hasWildcardAccess = $endpoints->contains(fn($e) => $e->fields === null);

            $allowedFields = $hasWildcardAccess
                ? collect($collection->schema)->pluck('name')->values()
                : $endpoints
                    ->pluck('fields')
                    ->filter()
                    ->flatten()
                    ->unique()
                    ->values();

            $filteredSchema = collect($collection->schema)
                ->filter(fn($field) => $allowedFields->contains($field['name']))
                ->values()
                ->all();

            return [
                'name' => $collection->name,
                'label' => $collection->label,
                'icon' => $collection->icon,
                'singleton' => $collection->singleton,
                'schema' => $filteredSchema,
                'endpoints' => $endpoints->map(function ($e) use ($collection) {
                    return [
                        'path' => $e->path,
                        'type' => $e->type,
                        'role' => $e->role,
                        'fields' => $e->fields ?? collect($collection->schema)->pluck('name')->all(),
                        'own_only' => $e->own_only,
                        'trigger_event' => $e->trigger_event,
                    ];
                })->values(),
            ];
        })->filter()->values();

        return response()->json($collections);
    }
}
