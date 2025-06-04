<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function create()
    {
        return Inertia::render('Register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'Ім\'я обов\'язкове',
            'name.max' => 'Довжину імені перебільшено',

            'password.required' => 'Пароль обов\'язковий',
            'password.confirmed' => 'Паролі не співпадають',

            'email.email' => 'Email адреса повинна відповідати формату',
            'email.required' => 'Email адреса обов\'язкова',
            'email.max' => 'Довжину email перебільшено',
            'email.unique' => 'Email адреса вже існує',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        auth()->login($user);

        return Inertia::location('/');
    }
}
