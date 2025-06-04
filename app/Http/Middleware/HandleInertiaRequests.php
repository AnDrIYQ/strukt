<?php

namespace App\Http\Middleware;

use App\Models\Collection;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'flash' => fn () => [
                'success' => session('success'),
                'error' => session('error'),
            ],
            'collectionsForSidebar' => fn () => auth()->check() && auth()->user()->role === 'admin'
                ? Collection::select('id', 'name', 'label', 'icon', 'singleton')->get()
                : [],
            'auth' => [
                'user' => fn () => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'role' => $request->user()->role,
                        'updated_at' => $request->user()->updated_at,
                    ]
                    : null,
            ],
            'errors' => fn () => $request->session()->get('errors')
                ? $request->session()->get('errors')->getBag('default')->toArray()
                : (object) [],
        ]);
    }
}
