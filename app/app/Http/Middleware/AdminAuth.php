<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Check session
        if (session('admin_authenticated')) {
            return $next($request);
        }

        // Check for login attempt
        if ($request->isMethod('post') && $request->path() === 'admin/login') {
            $email = $request->input('email');
            $password = $request->input('password');

            if ($email === env('ADMIN_EMAIL') && $password === env('ADMIN_PASSWORD')) {
                session(['admin_authenticated' => true]);
                return redirect('/admin');
            }

            return back()->with('error', 'Identifiants incorrects');
        }

        // Show login form
        if ($request->path() === 'admin/login' || $request->path() === 'admin') {
            return response()->view('admin.login');
        }

        return redirect('/admin/login');
    }
}
