<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // 1. The admin SPA redirects to /login?intended=/admin when it
        //    detects the user is not signed in. The themed login form
        //    also passes `intended` as a hidden field so the value is
        //    preserved across the POST. Honour either source — and
        //    reject anything that isn't a same-origin path to keep
        //    this from becoming an open-redirect.
        $intended = $request->input('intended') ?? $request->query('intended');
        if (is_string($intended) && str_starts_with($intended, '/') && ! str_starts_with($intended, '//')) {
            return redirect()->to($intended);
        }

        // 2. Otherwise: admins go straight to the admin panel, regular
        //    users to the public dashboard.
        $default = $request->user()?->isAdmin() ? '/admin' : route('dashboard', absolute: false);

        return redirect()->intended($default);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
