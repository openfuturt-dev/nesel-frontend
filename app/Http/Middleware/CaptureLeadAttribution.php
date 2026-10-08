<?php

namespace App\Http\Middleware;

use App\Support\LeadAttribution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureLeadAttribution
{
    /**
     * Record first-touch attribution for page views (see LeadAttribution).
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $request->hasSession()) {
            LeadAttribution::capture($request);
        }

        return $next($request);
    }
}
