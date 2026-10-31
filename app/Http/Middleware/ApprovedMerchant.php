<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use GazaDelivery\Foundation\Enums\StoreStatus;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApprovedMerchant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('merchant');
        if (! $user->isActive() || $user->store?->status !== StoreStatus::Active) {
            return $request->expectsJson()
                ? response()->json(['message' => 'الحساب لم يُفعّل بعد.'], 403)
                : redirect()->route('merchant.status');
        }

        return $next($request);
    }
}
