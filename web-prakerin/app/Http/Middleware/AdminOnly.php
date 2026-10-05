<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('admin')->check()) {
            return redirect()->route('login')->withErrors([
                'identifier' => 'Silakan login sebagai admin terlebih dahulu.',
            ]);
        }

        $admin = auth('admin')->user();

        $request->attributes->set('admin', $admin);

        return $next($request);
    }
}