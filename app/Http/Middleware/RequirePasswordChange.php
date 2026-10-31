<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequirePasswordChange
{
    public function handle(Request $request, Closure $next, string $guard): Response
    {
        if ($request->user($guard)->must_change_password) {
            return $request->expectsJson()
                ? response()->json(['message' => 'غيّر كلمة المرور المؤقتة أولًا.'], 403)
                : redirect()->route($guard.'.password.edit');
        }

        return $next($request);
    }
}
