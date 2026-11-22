<?php

namespace App\Http\Controllers;

use App\Services\RenterApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RenterApprovalController extends Controller
{
    /**
     * Display pending renter registrations.
     */
    public function index(
        RenterApprovalService $approvalService
    ): View {
        $pendingRenters = $approvalService
            ->getPendingRenters();

        return view(
            'admin.renters.pending',
            compact('pendingRenters')
        );
    }

    /**
     * Approve a pending renter registration.
     */
    public function approve(
        RenterApprovalService $approvalService,
        string $user
    ): RedirectResponse {
        $approvalService->approveRenter($user);

        return redirect()
            ->route('admin.renters.pending')
            ->with(
                'success',
                'Renter registration approved successfully.'
            );
    }

    /**
     * Reject a pending renter registration.
     */
    public function reject(
        RenterApprovalService $approvalService,
        string $user
    ): RedirectResponse {
        $approvalService->rejectRenter($user);

        return redirect()
            ->route('admin.renters.pending')
            ->with(
                'success',
                'Renter registration rejected successfully.'
            );
    }
}
