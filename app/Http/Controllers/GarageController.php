<?php

namespace App\Http\Controllers;

use App\Services\GarageService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GarageController extends Controller
{
    /**
     * Display all garages.
     */
    public function index(
        GarageService $garageService
    ): View {
        $garages = $garageService->getGarages();

        return view('garages.index', compact('garages'));
    }

    /**
     * Display the garage creation form.
     */
    public function create(): View
    {
        return view('garages.create');
    }

    /**
     * Store multiple garages.
     */
    public function store(
        Request $request,
        GarageService $garageService
    ): RedirectResponse {
        $validated = $request->validate([
            'garage_type' => [
                'required',
                Rule::in([
                    'bicycle',
                    'car',
                    'electric_car',
                ]),
            ],

            'quantity' => [
                'required',
                'integer',
                'min:1',
                'max:999',
            ],

            'daily_rate' => [
                'required',
                'integer',
                'min:1',
            ],

            'electricity_rate_per_kwh' => [
                'nullable',
                'required_if:garage_type,electric_car',
                'prohibited_unless:garage_type,electric_car',
                'integer',
                'min:1',
            ],
        ]);

        $garages = $garageService->createGarages($validated);

        return redirect()
            ->route('garages.index')
            ->with(
                'success',
                $garages->count() . ' garages created successfully.'
            );
    }
}
