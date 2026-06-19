<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($credentials['username'] !== config('admin.username') || $credentials['password'] !== config('admin.password')) {
            return back()->withErrors([
                'username' => 'Invalid credentials.',
            ])->onlyInput('username');
        }

        $request->session()->put('admin_authenticated', true);

        return redirect('/admin/campaigns');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('admin_authenticated');

        return redirect('/admin/login');
    }
}
