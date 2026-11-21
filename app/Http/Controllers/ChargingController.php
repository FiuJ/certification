<?php

namespace App\Http\Controllers;

use App\Services\ChargingService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ChargingController extends Controller
{
    /**
     * Display charging history for a rental.
     */
    public function index(
        Request $request,
        ChargingService $chargingService,
        string $rental
    ): View {
        $chargingRecords = $chargingService->getChargingRecords(
            $request->user(),
            $rental
        );

        return view('charging.index', [
            'chargingRecords' => $chargingRecords,
            'rentalId' => $rental,
        ]);
    }

    /**
     * Display the charging form.
     */
    public function create(
        Request $request,
        ChargingService $chargingService,
        string $rental
    ): View {
        $rentalData = $chargingService->getRentalForCharging(
            $request->user(),
            $rental
        );

        return view('charging.create', [
            'rental' => $rentalData,
        ]);
    }

    /**
     * Preview the charging cost before payment.
     */
    public function preview(
        Request $request,
        ChargingService $chargingService,
        string $rental
    ): View {
        $validated = $request->validate([
            'current_battery_percentage' => [
                'required',
                'numeric',
                'between:0,99.99',
                'decimal:0,2',
            ],
        ]);

        $preview = $chargingService->previewCharging(
            $request->user(),
            $rental,
            (float) $validated['current_battery_percentage']
        );

        return view('charging.preview', [
            'preview' => $preview,
            'rentalId' => $rental,
        ]);
    }

    /**
     * Store a new charging transaction.
     */
    public function store(
        Request $request,
        ChargingService $chargingService,
        string $rental
    ): RedirectResponse {
        $validated = $request->validate([
            'current_battery_percentage' => [
                'required',
                'numeric',
                'between:0,99.99',
                'decimal:0,2',
            ],

            'payment_proof' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        // Payment proof is not passed as database data.
        unset($validated['payment_proof']);

        $paymentPath = $this->storePaymentProof($request);

        try {
            $chargingService->createChargingRecord(
                $request->user(),
                $rental,
                $validated,
                $paymentPath
            );
        } catch (Throwable $exception) {
            // Remove the uploaded file if the transaction fails.
            Storage::disk('local')->delete($paymentPath);

            throw $exception;
        }

        return redirect()
            ->route('charging.index', [
                'rental' => $rental,
            ])
            ->with(
                'success',
                'Charging payment submitted successfully.'
            );
    }

    /**
     * Store charging payment proof in private storage.
     */
    private function storePaymentProof(
        Request $request
    ): string {
        $path = $request->file('payment_proof')
            ->store('charging-payments', 'local');

        if ($path === false) {
            throw ValidationException::withMessages([
                'payment_proof' =>
                    'Failed to upload charging payment proof.',
            ]);
        }

        return $path;
    }
}
