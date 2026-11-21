<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardEntryController extends Controller
{
    /**
     * Redirect authenticated users to their dashboard.
     */
    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === 'renter') {
            if ($user->registration_status === 'Approve') {
                return redirect()->route('renter.dashboard');
            }

            if (in_array(
                $user->registration_status,
                ['Pending', 'Reject'],
                true
            )) {
                return redirect()->route('registration.status');
            }
        }

        abort(403, 'Invalid account role or registration status.');
    }
}
