<?php

namespace App\Http\Controllers;

use App\Services\VehicleService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
{
    /**
     * Display the user's vehicles.
     */
    public function index(
        Request $request,
        VehicleService $vehicleService
    ): View {
        $vehicles = $vehicleService->getVehicles(
            $request->user()
        );

        return view('vehicles.index', compact('vehicles'));
    }

    /**
     * Display the vehicle creation form.
     */
    public function create(): View
    {
        return view('vehicles.create');
    }

    /**
     * Store a new vehicle.
     */
    public function store(
        Request $request,
        VehicleService $vehicleService
    ): RedirectResponse {
        $validated = $request->validate([
            'type' => [
                'required',
                Rule::in([
                    'bicycle',
                    'motorcycle',
                    'car',
                    'electric_car',
                ]),
            ],

            'license_plate' => [
                'nullable',
                'required_unless:type,bicycle',
                'string',
                'max:20',
                'unique:vehicles,license_plate',
            ],

            'brand' => [
                'required',
                'string',
                'max:100',
            ],

            'model' => [
                'required',
                'string',
                'max:100',
            ],

            'battery_capacity' => [
                'nullable',
                'required_if:type,electric_car',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],
        ]);

        $vehicleService->createVehicle(
            $request->user(),
            $validated
        );

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle added successfully.');
    }

    /**
     * Display vehicle details.
     */
    public function show(
        Request $request,
        VehicleService $vehicleService,
        string $vehicle
    ): View {
        $vehicle = $vehicleService->getVehicle(
            $request->user(),
            $vehicle
        );

        return view('vehicles.show', compact('vehicle'));
    }
}
