<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->is_active && ! $user->isDisabled()) {
            return $next($request);
        }

        $this->invalidateCurrentSession($request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tu usuario esta desactivado. Contacta con IT o con un administrador.',
            ], 403);
        }

        return redirect()
            ->route('login')
            ->with('error', 'Tu usuario esta desactivado. Contacta con IT o con un administrador.');
    }

    private function invalidateCurrentSession(Request $request): void
    {
        $userId = $request->user()?->id;

        Auth::guard()->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($userId) {
            DB::table('sessions')
                ->where('user_id', $userId)
                ->delete();
        }
    }
}
