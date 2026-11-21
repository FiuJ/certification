<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrationStatusController extends Controller
{
    /**
     * Display the renter's registration status.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'renter') {
            abort(403, 'Access restricted to renters.');
        }

        if ($user->registration_status === 'Approve') {
            return redirect()->route('renter.dashboard');
        }

        if (!in_array(
            $user->registration_status,
            ['Pending', 'Reject'],
            true
        )) {
            abort(403, 'Invalid registration status.');
        }

        return view('registration.status', [
            'status' => $user->registration_status,
        ]);
    }
}
