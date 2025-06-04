<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function create()
    {
        return Inertia::render('Login');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Обовязково введіть email',
            'email.email' => 'Email повинен відповідати формату',
            'password.required' => 'Обов\'язково введіть пароль',
        ]);

        if (!Auth::attempt($validated, true)) {
            return back()->withErrors([
                'email' => 'Невірний email або пароль',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return Inertia::location('/');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Inertia::location('/login');
    }
}
