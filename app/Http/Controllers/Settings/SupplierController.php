<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers.
     */
    public function index(): Response
    {
        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get();

        return Inertia::render('settings/Suppliers/Index', [
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);

        $supplier = Supplier::create($validated);

        return redirect()->route('settings.suppliers.index')
            ->with('success', 'เพิ่มซัพพลายเออร์สำเร็จ');
    }

    /**
     * Update the specified supplier.
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
        ]);

        $supplier->update($validated);

        return redirect()->route('settings.suppliers.index')
            ->with('success', 'อัปเดตซัพพลายเออร์สำเร็จ');
    }

    /**
     * Remove the specified supplier.
     */
    public function destroy(Supplier $supplier)
    {
        // Check if supplier has any ingredients
        if ($supplier->ingredients()->count() > 0) {
            return back()->withErrors([
                'message' => 'ไม่สามารถลบซัพพลายเออร์ได้ เนื่องจากมีวัตถุดิบที่เชื่อมโยงอยู่',
            ]);
        }

        $supplier->delete();

        return redirect()->route('settings.suppliers.index')
            ->with('success', 'ลบซัพพลายเออร์สำเร็จ');
    }
}
