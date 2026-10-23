<?php

namespace App\Http\Middleware;

use App\Services\ActiveUserService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ActiveUserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    protected $activeUserService;

    public function __construct(ActiveUserService $activeUserService)
    {
        $this->activeUserService = $activeUserService;
    }

    public function handle($request, Closure $next)
    {
        view()->share('sharedActiveUserData', $this->activeUserService->getActiveUser());

        return $next($request);
    }
}
