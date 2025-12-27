<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class IngredientController extends Controller
{
    public function index()
    {
        $ingredients = Ingredient::with('supplier')->latest()->get();
        return Inertia::render('Ingredients/Index', [
            'ingredients' => $ingredients
        ]);
    }

    public function create()
    {
        $suppliers = Supplier::all();
        $units = Unit::all();
        return Inertia::render('Ingredients/Create', [
            'suppliers' => $suppliers,
            'units' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_unit' => 'required|string|max:50',
            'cost_per_unit' => 'required|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
        ]);

        Ingredient::create($validated);

        return redirect()->route('ingredients.index')->with('success', 'Ingredient created successfully.');
    }

    public function receiveStock(Request $request, Ingredient $ingredient)
    {
        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.0001',
            'unit_cost' => 'nullable|numeric|min:0',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($ingredient, $validated) {
            // Create transaction
            $ingredient->transactions()->create([
                'type' => 'purchase',
                'quantity' => $validated['quantity'],
                'unit_cost' => $validated['unit_cost'] ?? $ingredient->cost_per_unit,
                'transaction_date' => $validated['transaction_date'],
                'notes' => $validated['notes'],
            ]);

            // Update stock
            $ingredient->increment('current_stock', $validated['quantity']);
            
            // Update cost if provided
            if (isset($validated['unit_cost'])) {
                $ingredient->update(['cost_per_unit' => $validated['unit_cost']]);
            }
        });

        return back()->with('success', 'Stock received successfully.');
    }
}
