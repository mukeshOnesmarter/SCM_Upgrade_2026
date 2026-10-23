<?php

namespace App\Http\Middleware;

use App\Services\GeneralSettingsService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GernalSettingsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    protected $generalSettingsService;

    public function __construct(GeneralSettingsService $generalSettingsService)
    {
        $this->generalSettingsService = $generalSettingsService;
    }

    public function handle(Request $request, Closure $next)
    {
        view()->share('gernalSettings', $this->generalSettingsService->generalSettings());

        return $next($request);
    }
}
