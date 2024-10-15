<?php

namespace App\Http\Middleware;

use App\Helpers\SsoSimakHelper;
use Closure;
use Illuminate\Http\Request;

class AuthSimakMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $login = SsoSimakHelper::loginFromToken();
        if (!$login) return SsoSimakHelper::getInstance()->generateLoginUrl();

        return $next($request);
    }
}
