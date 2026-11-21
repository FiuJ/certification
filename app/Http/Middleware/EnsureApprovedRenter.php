<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedRenter
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if ($user?->role !== 'renter') {
            abort(
                403,
                'Access restricted to renters.'
            );
        }

        if (in_array(
            $user->registration_status,
            ['Pending', 'Reject'],
            true
        )) {
            return redirect()->route(
                'registration.status'
            );
        }

        if ($user->registration_status !== 'Approve') {
            abort(
                403,
                'Invalid registration status.'
            );
        }

        return $next($request);
    }
}
