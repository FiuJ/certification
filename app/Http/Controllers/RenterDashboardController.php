<?php

namespace App\Http\Controllers;

use App\Services\RentalService;
use App\Services\VehicleService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RenterDashboardController extends Controller
{
    /**
     * Display the approved renter's dashboard.
     */
    public function index(
        Request $request,
        VehicleService $vehicleService,
        RentalService $rentalService
    ): View {
        $vehicles = $vehicleService->getVehicles(
            $request->user()
        );

        $rentals = $rentalService->getRentals(
            $request->user()
        )->load(['vehicle', 'garage']);

        return view('renter.dashboard', compact(
            'vehicles',
            'rentals'
        ));
    }
}
