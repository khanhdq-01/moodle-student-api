<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeacherMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->hasRole(Auth::id(), 4)) { // 4 = teacher
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
    }

    private function hasRole($userId, $roleId)
    {
        return DB::table('mdl_role_assignments as ra')
            ->join('mdl_context as ctx', 'ra.contextid', '=', 'ctx.id')
            ->where('ra.userid', $userId)
            ->where('ra.roleid', $roleId)
            ->exists();
    }
}
