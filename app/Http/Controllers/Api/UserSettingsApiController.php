<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserSettingsApiController extends Controller
{
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_public' => ['boolean'],
            'avatar' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp'],
        ]);

        if ($request->filled('name')) {
            $user->name = $validated['name'];
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        if ($request->filled('is_public')) {
            $user->is_public = $validated['is_public'];
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('local')->exists($user->avatar)) {
                Storage::disk('local')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars/' . $user->id, 'local');
            $user->avatar = $path;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'is_public' => $user->is_public,
                'avatar' => $user->avatar
                    ? route('avatar.public', ['user' => $user->id], false)
                    : null,
            ]
        ]);
    }
}
