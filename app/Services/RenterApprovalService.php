<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RenterApprovalService
{
    /**
     * Get all pending renter registrations.
     */
    public function getPendingRenters(): Collection
    {
        return User::query()
            ->where('role', 'renter')
            ->where('registration_status', 'Pending')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Approve a pending renter.
     */
    public function approveRenter(string $userId): User
    {
        return $this->updateRegistrationStatus(
            $userId,
            'Approve'
        );
    }

    /**
     * Reject a pending renter.
     */
    public function rejectRenter(string $userId): User
    {
        return $this->updateRegistrationStatus(
            $userId,
            'Reject'
        );
    }

    /**
     * Update the registration status of a pending renter.
     */
    private function updateRegistrationStatus(
        string $userId,
        string $status
    ): User {
        return DB::transaction(function () use (
            $userId,
            $status
        ) {
            $renter = User::query()
                ->where('user_id', $userId)
                ->where('role', 'renter')
                ->lockForUpdate()
                ->firstOrFail();

            if ($renter->registration_status !== 'Pending') {
                throw ValidationException::withMessages([
                    'registration_status' =>
                        'Only pending registrations can be reviewed.',
                ]);
            }

            $renter->registration_status = $status;
            $renter->save();

            return $renter;
        }, 3);
    }
}
