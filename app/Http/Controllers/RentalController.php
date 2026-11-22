<?php

namespace App\Http\Controllers;

use App\Services\GarageService;
use App\Services\RentalService;
use App\Services\VehicleService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;
use Carbon\CarbonImmutable;
use Illuminate\View\View;
use Throwable;

class RentalController extends Controller
{
    /**
     * Display the renter's rental history.
     */
    public function index(
        Request $request,
        RentalService $rentalService,
        GarageService $garageService
    ): View {
        // Refresh garage statuses when the rental page is opened.
        $garageService->syncGarageStatuses();

        $rentals = $rentalService->getRentals(
            $request->user()
        );

        return view('rentals.index', compact('rentals'));
    }

    /**
     * Display the rental creation form.
     */
    public function create(
        Request $request,
        GarageService $garageService,
        VehicleService $vehicleService
    ): View {
        // Refresh garage statuses before creating a rental.
        $garageService->syncGarageStatuses();

        // Only the renter's own vehicles can be rented for.
        $vehicles = $vehicleService->getVehicles(
            $request->user()
        );

        return view('rentals.create', compact('vehicles'));
    }

    /**
     * Store a new rental or Buy More transaction.
     */
    public function store(
        Request $request,
        RentalService $rentalService
    ): RedirectResponse {

        // Validate the start date before calculating the duration limit.
        $dateData = $request->validate([
            'start_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
        ]);

        // Use the selected start date, or today if not provided.
        $startDate = CarbonImmutable::parse(
            $dateData['start_date'] ?? now()->toDateString()
        );

        // Maximum daily duration = days in starting month - 1.
        $maxDailyDuration = $startDate->daysInMonth - 1;


        $validated = $request->validate([
            'vehicle_id' => [
                'required',
                'uuid',
            ],

            // Electric cars may select a normal or charging garage.
            // Vehicle compatibility is checked by RentalService.
            'garage_type' => [
                'nullable',
                Rule::in([
                    'bicycle',
                    'car',
                    'electric_car',
                ]),
            ],

            'rental_type' => [
                'required',
                Rule::in(['daily', 'monthly']),
            ],
            'start_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'duration' => [
                'nullable',
                'required_if:rental_type,daily',
                'prohibited_if:rental_type,monthly',
                'integer',
                'min:1',
                'max:'. $maxDailyDuration,
            ],
            'payment_proof' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        // Use today when no start date is provided.
        $validated['start_date'] =
            $validated['start_date'] ?? now()->toDateString();

        unset($validated['payment_proof']);

        $paymentPath = $this->storePaymentProof($request);

        try {
            $rentalService->createRental(
                $request->user(),
                $validated,
                $paymentPath
            );
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paymentPath);

            throw $exception;
        }

        return redirect()
            ->route('rentals.index')
            ->with('success', 'Rental created successfully.');
    }

    /**
     * Display the details of a rental.
     */
    public function show(
        Request $request,
        RentalService $rentalService,
        string $rental
    ): View {
        $rental = $rentalService->getRental(
            $request->user(),
            $rental
        );

        return view('rentals.show', compact('rental'));
    }

    /**
     * Display all original rentals for administrators.
     */
    public function adminIndex(
        RentalService $rentalService
    ): View {
        $rentals = $rentalService->getAdminRentals();

        // Last occupied date of each rental, including extensions.
        $lastOccupiedDates = $rentals->mapWithKeys(
            fn ($rental) => [
                $rental->rental_id =>
                    $rentalService->getLatestEndDate($rental)->subDay(),
            ]
        );

        return view('admin.rentals.index', compact(
            'rentals',
            'lastOccupiedDates'
        ));
    }

    /**
     * Display the rental extension form for administrators.
     */
    public function extension(
        RentalService $rentalService,
        string $rental
    ): View {
        $rental = $rentalService->getAdminRental($rental);

        $latestEndDate = $rentalService->getLatestEndDate($rental);

        return view('admin.rentals.extend', [
            'rental' => $rental,
            // The next extension starts on the exclusive end date.
            'extensionStartDate' => $latestEndDate,
            'lastOccupiedDate' => $latestEndDate->subDay(),
        ]);
    }

    /**
     * Extend an existing rental.
     * Only administrators can perform this action.
     */
    public function extend(
        Request $request,
        RentalService $rentalService,
        string $rental
    ): RedirectResponse {
        $validated = $request->validate([
            // Admin selects the last occupied day (inclusive).
            // RentalService calculates the total extension days.
            'end_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'payment_proof' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        unset($validated['payment_proof']);

        $paymentPath = $this->storePaymentProof($request);

        try {
            $rentalService->extendRental(
                $request->user(),
                $rental,
                $validated,
                $paymentPath
            );
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($paymentPath);

            throw $exception;
        }

        return back()
            ->with('success', 'Rental extended successfully.');
    }


    /**
     * Return a price quote for a new rental.
     */
    public function quote(
        Request $request,
        RentalService $rentalService
    ): JsonResponse {
        $dateData = $request->validate([
            'start_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
        ]);

        $startDate = CarbonImmutable::parse(
            $dateData['start_date']
        );

        $validated = $request->validate([
            'vehicle_id' => ['required', 'uuid'],
            'garage_type' => [
                'nullable',
                Rule::in(['bicycle', 'car', 'electric_car']),
            ],
            'rental_type' => [
                'required',
                Rule::in(['daily', 'monthly']),
            ],
            'start_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'duration' => [
                'nullable',
                'required_if:rental_type,daily',
                'prohibited_if:rental_type,monthly',
                'integer',
                'min:1',
                'max:'.($startDate->daysInMonth - 1),
            ],
        ]);

        return response()->json(
            $rentalService->quoteRental(
                $request->user(),
                $validated
            )
        );
    }

    /**
     * Return an extension price quote for administrators.
     */
    public function quoteExtension(
        Request $request,
        RentalService $rentalService,
        string $rental
    ): JsonResponse {
        $validated = $request->validate([
            'end_date' => [
                'required',
                'date_format:Y-m-d',
            ],
        ]);

        return response()->json(
            $rentalService->quoteExtension(
                $request->user(),
                $rental,
                $validated['end_date']
            )
        );
    }


    /**
     * Store payment proof in private storage.
     */
    private function storePaymentProof(Request $request): string
    {
        $path = $request->file('payment_proof')
            ->store('rental-payments', 'local');

        if ($path === false) {
            throw ValidationException::withMessages([
                'payment_proof' => 'Failed to upload payment proof.',
            ]);
        }

        return $path;
    }
}
