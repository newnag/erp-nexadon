<?php

namespace App\Http\Controllers;

use App\Models\FinancialCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinancialCategoryController extends Controller
{
    public function index()
    {
        $categories = FinancialCategory::withCount('transactions')
            ->latest()
            ->get();

        return Inertia::render('Finance/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:20',
        ]);

        FinancialCategory::create($validated);

        return back()->with('success', 'สร้างหมวดหมู่สำเร็จ');
    }

    public function update(Request $request, FinancialCategory $financialCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $financialCategory->update($validated);

        return back()->with('success', 'อัพเดทหมวดหมู่สำเร็จ');
    }

    public function destroy(FinancialCategory $financialCategory)
    {
        if ($financialCategory->transactions()->exists()) {
            return back()->with('error', 'ไม่สามารถลบหมวดหมู่ที่มีรายการธุรกรรมได้');
        }

        $financialCategory->delete();

        return back()->with('success', 'ลบหมวดหมู่สำเร็จ');
    }
}
