<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserSettingsRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UserSettingsController extends Controller
{
    public function edit()
    {
        $user = auth()->user();

        return Inertia::render('Settings/Edit', [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'is_public' => $user->is_public,
            ],
        ]);
    }

    public function update(UpdateUserSettingsRequest  $request)
    {
        $user = auth()->user();

        $validated = $request->validated();

        if ($request->has('name')) {
            $user->name = $validated['name'];
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($validated['password']);
        }

        $user->is_public = $validated['is_public'];

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('local')->exists($user->avatar)) {
                Storage::disk('local')->delete($user->avatar);
            }

            $path = $request->file('avatar')->store('avatars/' . $user->id, 'local');
            $user->avatar = $path;
        }

        $user->save();

        return back()->with('success', 'Профіль оновлено');
    }
}
