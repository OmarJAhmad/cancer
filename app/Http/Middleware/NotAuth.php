<?php

namespace App\Http\Middleware;

use Auth;
use Closure;

class NotAuth {
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next) {

		if (Auth::guard('web')->check()) {
			return back();
		}

		return $next($request);
	}
}
