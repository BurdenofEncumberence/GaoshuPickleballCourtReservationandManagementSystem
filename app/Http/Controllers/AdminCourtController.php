<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Services\CourtUtilizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCourtController extends Controller
{
    public function index(CourtUtilizationService $utilizationService): View
    {
        return view('admin.courts.index', [
            'courts' => Court::withCount('bookings')->orderBy('id')->get(),
            'utilization' => $utilizationService->getFacilityMetrics(),
            'courtUtilization' => $utilizationService->getCourtBreakdown(),
        ]);
    }

    public function create(): View
    {
        return view('admin.courts.form', [
            'court' => new Court([
                'size' => 'Regular (13.41m x 6.10m)',
                'court_status' => 'available',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Court::create($this->validatedData($request));

        return redirect()->route('admin.courts.index')->with('status', 'Court added to your club.');
    }

    public function edit(Court $court): View
    {
        return view('admin.courts.form', compact('court'));
    }

    public function update(Request $request, Court $court): RedirectResponse
    {
        $court->update($this->validatedData($request));

        return redirect()->route('admin.courts.index')->with('status', 'Court details updated.');
    }

    public function destroy(Court $court): RedirectResponse
    {
        if ($court->bookings()->exists()) {
            return back()->with('error', 'This court has bookings and cannot be deleted. Mark it maintenance instead.');
        }

        $court->delete();

        return redirect()->route('admin.courts.index')->with('status', 'Court removed.');
    }

    private function validatedData(Request $request): array
    {
        if (! $request->filled('court_status')) {
            $request->merge(['court_status' => 'available']);
        }

        return $request->validate([
            'court_name' => ['required', 'string', 'max:100'],
            'size' => ['required', 'string', 'max:50'],
            'price_per_hour' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'court_status' => ['required', 'in:available,maintenance,closed'],
        ]);
    }
}
