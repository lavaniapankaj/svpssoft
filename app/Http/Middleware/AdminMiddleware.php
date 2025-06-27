<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Admin\SessionMaster;
use Illuminate\Support\Facades\Session;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $softSection): Response
    {
        $user = Auth::user();
        if ($softSection == 'admin') {
            # code...
            if ($user->role_id == 1) {
                $current_session = SessionMaster::select('id', 'session')->where('active', 1)->where('admin_current_session', 1)->first();
                Session::put('current_session', $current_session);
                Session::put('login_user', $user->id);
                return $next($request);
            }
        } elseif ($softSection == 'fee') {
            # code...
            $current_session = SessionMaster::select('id', 'session')->where('active', 1)->where('fee_current_session', 1)->first();
            Session::put('fee_current_session', $current_session);
            Session::put('login_user', $user->id);
            if ($user->role_id == 1 || $user->role_id == 2) {
                return $next($request);
            }
        } elseif ($softSection == 'student') {
            # code...
            $current_session = SessionMaster::select('id', 'session')->where('active', 1)->where('student_current_session', 1)->first();
            Session::put('std_current_session', $current_session);
            Session::put('login_user', $user->id);
            if ($user->role_id == 1 || $user->role_id == 3) {
                return $next($request);
            }
        } elseif ($softSection == 'marks') {
            # code...
            $current_session = SessionMaster::select('id', 'session')->where('active', 1)->where('marks_current_session', 1)->first();
            Session::put('marks_current_session', $current_session);
            Session::put('login_user', $user->id);
            if ($user->role_id == 1 || $user->role_id == 4) {
                return $next($request);
            }
        } elseif ($softSection == 'inventory') {
            # code...
            Session::put('login_user', $user->id);
            if ($user->role_id == 1 || $user->role_id == 5) {
                return $next($request);
            }
        }

        return abort(403, "You can't access this page");
    }
}
