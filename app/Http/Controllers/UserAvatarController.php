<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserAvatarController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        if (!$user->avatar || !Storage::disk('local')->exists($user->avatar)) {
            abort(404);
        }

        return response()->file(
            storage_path('app/private/' . $user->avatar),
            ['Content-Type' => mime_content_type(storage_path('app/private/' . $user->avatar))]
        );
    }

    public function showSigned(User $user)
    {
        if (!$user->avatar || !Storage::disk('local')->exists($user->avatar)) {
            abort(404);
        }
        return Storage::disk('local')->response($user->avatar);
    }

    public function showPublic(User $user)
    {
        if (!$user->is_public || !$user->avatar || !Storage::disk('local')->exists($user->avatar)) {
            abort(404);
        }

        return response()->file(
            storage_path('app/private/' . $user->avatar),
            ['Content-Type' => mime_content_type(storage_path('app/private/' . $user->avatar))]
        );
    }
}
