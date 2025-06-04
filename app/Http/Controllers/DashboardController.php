<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\EndPoint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $userStats = [
            'adminsCount' => User::where('role', 'admin')->count(),
            'usersCount' => User::where('role', 'user')->count(),
        ];

        $adminStats = [
            'collectionsCount' => Collection::count(),
            'endpointsCount' => EndPoint::count(),
            'singletonCount' => Collection::where('singleton', true)->count(),
            'entries' => DB::table('collection_entries')->get()->count(),
        ];

        return Inertia::render('Index', [
            'userStats' => $userStats,
            'adminStats' => $adminStats,
        ]);
    }
}

