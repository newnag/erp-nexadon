<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\Ingredient;
use App\Models\Unit;
use App\Models\UnitConversion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = Recipe::latest()->get();
        return Inertia::render('Recipes/Index', [
            'recipes' => $recipes
        ]);
    }

    public function create()
    {
        $ingredients = Ingredient::all();
        $units = Unit::all();
        return Inertia::render('Recipes/Create', [
            'ingredients' => $ingredients,
            'units' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|max:2048',
            'yield_quantity' => 'required|numeric|min:0.1',
            'yield_unit' => 'required|string|max:50',
            'selling_price' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
            'overhead_cost' => 'nullable|numeric|min:0',
            'packaging_cost' => 'nullable|numeric|min:0',
            'ingredients' => 'array',
            'ingredients.*.id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.0001',
            'ingredients.*.unit' => 'required|string',
            'steps' => 'array',
            'steps.*.step_number' => 'required|integer',
            'steps.*.instruction' => 'required|string',
            'steps.*.image' => 'nullable|image|max:2048',
        ]);

        DB::transaction(function () use ($validated, $request) {
            // Handle recipe main image upload
            $recipeImageUrl = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $path = $image->store('recipe-images', 'public');
                $recipeImageUrl = Storage::url($path);
            }
            
            $recipe = Recipe::create([
                'name' => $validated['name'],
                'image_url' => $recipeImageUrl,
                'description' => $validated['description'] ?? null,
                'yield_quantity' => $validated['yield_quantity'],
                'yield_unit' => $validated['yield_unit'],
                'selling_price' => $validated['selling_price'] ?? null,
                'labor_cost' => $validated['labor_cost'] ?? 0,
                'overhead_cost' => $validated['overhead_cost'] ?? 0,
                'packaging_cost' => $validated['packaging_cost'] ?? 0,
            ]);

            $ingredientCost = 0;

            if (!empty($validated['ingredients'])) {
                foreach ($validated['ingredients'] as $item) {
                    $ingredient = Ingredient::find($item['id']);
                    
                    // แปลงหน่วยก่อนคำนวณต้นทุน
                    $quantityInPurchaseUnit = $this->convertToPurchaseUnit(
                        $item['quantity'],
                        $item['unit'],
                        $ingredient->purchase_unit
                    );
                    
                    // คำนวณต้นทุน: ราคาต่อหน่วยซื้อ × จำนวนในหน่วยซื้อ
                    $cost = $ingredient->cost_per_unit * $quantityInPurchaseUnit;
                    $ingredientCost += $cost;

                    $recipe->ingredients()->attach($item['id'], [
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit']
                    ]);
                }
            }

            if (!empty($validated['steps'])) {
                foreach ($validated['steps'] as $index => $stepData) {
                    $imageUrl = null;
                    
                    // Handle image upload
                    if ($request->hasFile("steps.{$index}.image")) {
                        $image = $request->file("steps.{$index}.image");
                        $path = $image->store('recipe-steps', 'public');
                        $imageUrl = Storage::url($path);
                    }
                    
                    $recipe->steps()->create([
                        'step_number' => $stepData['step_number'],
                        'instruction' => $stepData['instruction'],
                        'image_url' => $imageUrl,
                    ]);
                }
            }

            // คำนวณต้นทุนรวมทั้งหมด
            $totalCost = $ingredientCost + ($validated['labor_cost'] ?? 0) + ($validated['overhead_cost'] ?? 0) + ($validated['packaging_cost'] ?? 0);
            $recipe->update(['total_cost' => $totalCost]);
        });

        return redirect()->route('recipes.index')->with('success', 'Recipe created successfully.');
    }

    /**
     * แปลงจำนวนจากหน่วยที่ใช้ในสูตรเป็นหน่วยซื้อ
     */
    private function convertToPurchaseUnit(float $quantity, string $fromUnit, string $toUnit): float
    {
        $converted = UnitConversion::convert($quantity, $fromUnit, $toUnit);
        
        // ถ้าแปลงไม่ได้ (ไม่มีในตาราง) ให้ใช้ค่าเดิม (สมมติว่าหน่วยเดียวกัน)
        return $converted ?? $quantity;
    }

    public function show(Recipe $recipe)
    {
        $recipe->load(['ingredients', 'steps']);
        return Inertia::render('Recipes/Show', [
            'recipe' => $recipe
        ]);
    }

    public function print(Recipe $recipe)
    {
        $recipe->load(['ingredients', 'steps']);
        return Inertia::render('Recipes/Print', [
            'recipe' => $recipe
        ]);
    }

    public function edit(Recipe $recipe)
    {
        $recipe->load(['ingredients', 'steps']);
        $ingredients = Ingredient::all();
        $units = Unit::all();
        
        return Inertia::render('Recipes/Edit', [
            'recipe' => $recipe,
            'ingredients' => $ingredients,
            'units' => $units,
        ]);
    }

    public function update(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'nullable',
            'yield_quantity' => 'required|numeric|min:0.1',
            'yield_unit' => 'required|string|max:50',
            'selling_price' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
            'overhead_cost' => 'nullable|numeric|min:0',
            'packaging_cost' => 'nullable|numeric|min:0',
            'ingredients' => 'array',
            'ingredients.*.id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.0001',
            'ingredients.*.unit' => 'required|string',
            'steps' => 'array',
            'steps.*.step_number' => 'required|integer',
            'steps.*.instruction' => 'required|string',
            'steps.*.image' => 'nullable|image|max:2048',
            'steps.*.remove_image' => 'nullable',
        ]);

        DB::transaction(function () use ($validated, $recipe, $request) {
            // Handle recipe main image
            $recipeImageUrl = $recipe->image_url;
            
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($recipe->image_url) {
                    $oldPath = str_replace('/storage/', '', $recipe->image_url);
                    Storage::disk('public')->delete($oldPath);
                }
                // Upload new image
                $image = $request->file('image');
                $path = $image->store('recipe-images', 'public');
                $recipeImageUrl = Storage::url($path);
            } elseif ($request->input('remove_image') == '1') {
                // Delete image if remove flag is set
                if ($recipe->image_url) {
                    $oldPath = str_replace('/storage/', '', $recipe->image_url);
                    Storage::disk('public')->delete($oldPath);
                }
                $recipeImageUrl = null;
            }
            
            $recipe->update([
                'name' => $validated['name'],
                'image_url' => $recipeImageUrl,
                'description' => $validated['description'] ?? null,
                'yield_quantity' => $validated['yield_quantity'],
                'yield_unit' => $validated['yield_unit'],
                'selling_price' => $validated['selling_price'] ?? null,
                'labor_cost' => $validated['labor_cost'] ?? 0,
                'overhead_cost' => $validated['overhead_cost'] ?? 0,
                'packaging_cost' => $validated['packaging_cost'] ?? 0,
            ]);

            // Sync ingredients
            $recipe->ingredients()->detach();
            $ingredientCost = 0;

            if (!empty($validated['ingredients'])) {
                foreach ($validated['ingredients'] as $item) {
                    $ingredient = Ingredient::find($item['id']);
                    
                    // แปลงหน่วยก่อนคำนวณต้นทุน
                    $quantityInPurchaseUnit = $this->convertToPurchaseUnit(
                        $item['quantity'],
                        $item['unit'],
                        $ingredient->purchase_unit
                    );
                    
                    // คำนวณต้นทุน: ราคาต่อหน่วยซื้อ × จำนวนในหน่วยซื้อ
                    $cost = $ingredient->cost_per_unit * $quantityInPurchaseUnit;
                    $ingredientCost += $cost;

                    $recipe->ingredients()->attach($item['id'], [
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit']
                    ]);
                }
            }

            // Delete old step images from storage
            foreach ($recipe->steps as $oldStep) {
                if ($oldStep->image_url) {
                    $oldPath = str_replace('/storage/', '', $oldStep->image_url);
                    Storage::disk('public')->delete($oldPath);
                }
            }
            
            // Sync steps
            $recipe->steps()->delete();
            if (!empty($validated['steps'])) {
                foreach ($validated['steps'] as $index => $stepData) {
                    $imageUrl = null;
                    
                    // Handle image upload
                    if ($request->hasFile("steps.{$index}.image")) {
                        $image = $request->file("steps.{$index}.image");
                        $path = $image->store('recipe-steps', 'public');
                        $imageUrl = Storage::url($path);
                    }
                    
                    $recipe->steps()->create([
                        'step_number' => $stepData['step_number'],
                        'instruction' => $stepData['instruction'],
                        'image_url' => $imageUrl,
                    ]);
                }
            }

            // คำนวณต้นทุนรวมทั้งหมด
            $totalCost = $ingredientCost + ($validated['labor_cost'] ?? 0) + ($validated['overhead_cost'] ?? 0) + ($validated['packaging_cost'] ?? 0);
            $recipe->update(['total_cost' => $totalCost]);
        });

        return redirect()->route('recipes.show', $recipe)->with('success', 'Recipe updated successfully.');
    }

    public function destroy(Recipe $recipe)
    {
        DB::transaction(function () use ($recipe) {
            $recipe->ingredients()->detach();
            $recipe->steps()->delete();
            $recipe->delete();
        });

        return redirect()->route('recipes.index')->with('success', 'Recipe deleted successfully.');
    }
}
