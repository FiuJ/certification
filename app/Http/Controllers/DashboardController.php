<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard and rental reports.
     */
    public function index(
        Request $request,
        DashboardService $dashboardService
    ): View {
        // Protect financial reports until middleware is added.
        abort_unless(
            $request->user()?->role === 'admin',
            403,
            'Only administrators can access the dashboard.'
        );

        // Validate the selected reporting period.
        $validated = $request->validate([
            'year' => [
                'sometimes',
                'required',
                'integer',
                'between:1000,9999',
            ],
            'month' => [
                'sometimes',
                'required',
                'integer',
                'between:1,12',
            ],
        ]);

        // Use the current year and month by default.
        $year = (int) (
            $validated['year'] ?? now()->year
        );

        $month = (int) (
            $validated['month'] ?? now()->month
        );

        return view('dashboard.index', [
            'summary' =>
                $dashboardService->getSummary(),

            'monthlyReport' =>
                $dashboardService->getMonthlyRentalReport(
                    $year
                ),

            'dailyReport' =>
                $dashboardService->getDailyRentalReport(
                    $year,
                    $month
                ),

            'garageTypeReport' =>
                $dashboardService->getGarageTypeReport(),

            'renterReport' =>
                $dashboardService->getRenterReport(),

            'selectedYear' => $year,
            'selectedMonth' => $month,
        ]);
    }
}
