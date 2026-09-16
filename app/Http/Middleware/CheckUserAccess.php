<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $module
     * @param  string|null  $action
     */
    public function handle(Request $request, Closure $next, string $module, ?string $action = null): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'unauthenticated',
                    'message' => 'Silakan login terlebih dahulu.',
                ], 401);
            }

            return redirect()->route('login');
        }

        // 1. Super Admin trigger: is_admin == true has full unrestricted access
        if ($user->is_admin) {
            return $next($request);
        }

        // 2. Resolve action if not explicitly given
        $resolvedAction = $action ?? $this->resolveActionFromRoute($request);

        // 3. Check granular access in users_access table
        if ($user->hasAccess($module, $resolvedAction)) {
            return $next($request);
        }

        // 4. Forbidden response if permission not granted
        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'forbidden',
                'message' => "Anda tidak memiliki hak akses [{$resolvedAction}] untuk modul [{$module}].",
            ], 403);
        }

        abort(403, "Anda tidak memiliki hak akses [{$resolvedAction}] untuk modul [{$module}].");
    }

    /**
     * Auto-resolve action from HTTP method / route action.
     */
    private function resolveActionFromRoute(Request $request): string
    {
        $route = $request->route();
        if ($route) {
            $routeName = $route->getName();
            if ($routeName) {
                $parts = explode('.', $routeName);
                $lastPart = end($parts);

                return match ($lastPart) {
                    'index' => 'index',
                    'show' => 'show',
                    'create', 'store' => 'create',
                    'edit', 'update' => 'edit',
                    'destroy' => 'delete',
                    default => 'index',
                };
            }
        }

        return match ($request->method()) {
            'GET' => 'index',
            'POST' => 'create',
            'PUT', 'PATCH' => 'edit',
            'DELETE' => 'delete',
            default => 'index',
        };
    }
}