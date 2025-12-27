<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    /**
     * Display a listing of units.
     */
    public function index(): Response
    {
        $units = Unit::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('settings/Units/Index', [
            'units' => $units,
        ]);
    }

    /**
     * Store a newly created unit.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:units,name'],
            'abbreviation' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $unit = Unit::create($validated);

        return redirect()->route('settings.units.index')
            ->with('success', 'เพิ่มหน่วยวัดสำเร็จ');
    }

    /**
     * Update the specified unit.
     */
    public function update(Request $request, Unit $unit)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:units,name,' . $unit->id],
            'abbreviation' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $unit->update($validated);

        return redirect()->route('settings.units.index')
            ->with('success', 'อัปเดตหน่วยวัดสำเร็จ');
    }

    /**
     * Remove the specified unit.
     */
    public function destroy(Unit $unit)
    {
        $unit->delete();

        return redirect()->route('settings.units.index')
            ->with('success', 'ลบหน่วยวัดสำเร็จ');
    }
}
