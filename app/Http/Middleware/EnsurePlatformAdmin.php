<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user()?->fresh();

        if ($user === null || ! $user->isPlatformAdmin()) {
            abort(403, 'Platform administrator access is required.');
        }

        return $next($request);
    }
}
