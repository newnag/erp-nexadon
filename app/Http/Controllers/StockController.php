<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use App\Models\InventoryTransaction;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockController extends Controller
{
    /**
     * Stock Dashboard - แสดงภาพรวมสต็อค
     */
    public function index()
    {
        // สรุปสต็อค
        $totalItems = Ingredient::count();
        $totalValue = Ingredient::sum(DB::raw('current_stock * cost_per_unit'));
        $lowStockItems = Ingredient::whereColumn('current_stock', '<=', 'reorder_point')->count();
        
        // วัตถุดิบที่สต็อคต่ำ
        $lowStockIngredients = Ingredient::with('supplier')
            ->whereColumn('current_stock', '<=', 'reorder_point')
            ->orderBy('current_stock')
            ->limit(10)
            ->get();

        // วัตถุดิบทั้งหมดพร้อมสถานะ
        $ingredients = Ingredient::with('supplier')
            ->select('ingredients.*')
            ->selectRaw('CASE WHEN current_stock <= reorder_point THEN "low" WHEN current_stock <= reorder_point * 2 THEN "medium" ELSE "normal" END as stock_status')
            ->orderBy('name')
            ->get();

        // การเคลื่อนไหวล่าสุด
        $recentTransactions = InventoryTransaction::with('ingredient')
            ->latest('transaction_date')
            ->limit(10)
            ->get();

        // สรุปการเคลื่อนไหวรายเดือน (6 เดือนล่าสุด)
        $monthlyMovement = InventoryTransaction::select(
                DB::raw('DATE_FORMAT(transaction_date, "%Y-%m") as month'),
                DB::raw('SUM(CASE WHEN type IN ("purchase", "adjustment_in") THEN quantity ELSE 0 END) as stock_in'),
                DB::raw('SUM(CASE WHEN type IN ("usage", "adjustment_out", "waste") THEN quantity ELSE 0 END) as stock_out')
            )
            ->where('transaction_date', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return Inertia::render('Stock/Index', [
            'summary' => [
                'totalItems' => $totalItems,
                'totalValue' => round($totalValue, 2),
                'lowStockItems' => $lowStockItems,
            ],
            'lowStockIngredients' => $lowStockIngredients,
            'ingredients' => $ingredients,
            'recentTransactions' => $recentTransactions,
            'monthlyMovement' => $monthlyMovement,
        ]);
    }

    /**
     * หน้ารับสินค้าเข้าสต็อค
     */
    public function stockInCreate()
    {
        $ingredients = Ingredient::with('supplier')->orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return Inertia::render('Stock/StockIn', [
            'ingredients' => $ingredients,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * บันทึกการรับสินค้าเข้าสต็อค
     */
    public function stockInStore(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $item) {
                $ingredient = Ingredient::findOrFail($item['ingredient_id']);
                
                // สร้าง transaction
                InventoryTransaction::create([
                    'ingredient_id' => $item['ingredient_id'],
                    'type' => 'purchase',
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'] ?? $ingredient->cost_per_unit,
                    'transaction_date' => $validated['transaction_date'],
                    'notes' => trim(($validated['reference_number'] ?? '') . ' ' . ($item['notes'] ?? '')),
                ]);

                // อัปเดตสต็อค
                $ingredient->increment('current_stock', $item['quantity']);

                // อัปเดตราคาต้นทุนถ้ามีการระบุ
                if (isset($item['unit_cost']) && $item['unit_cost'] > 0) {
                    $ingredient->update(['cost_per_unit' => $item['unit_cost']]);
                }
            }
        });

        return redirect()->route('stock.index')->with('success', 'รับสินค้าเข้าสต็อคเรียบร้อยแล้ว');
    }

    /**
     * หน้าเบิกสินค้าออกจากสต็อค
     */
    public function stockOutCreate()
    {
        $ingredients = Ingredient::with('supplier')
            ->where('current_stock', '>', 0)
            ->orderBy('name')
            ->get();

        return Inertia::render('Stock/StockOut', [
            'ingredients' => $ingredients,
            'transactionTypes' => [
                ['value' => 'usage', 'label' => 'เบิกใช้งาน'],
                ['value' => 'waste', 'label' => 'เสียหาย/หมดอายุ'],
                ['value' => 'adjustment_out', 'label' => 'ปรับลดสต็อค'],
            ],
        ]);
    }

    /**
     * บันทึกการเบิกสินค้าออกจากสต็อค
     */
    public function stockOutStore(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.type' => 'required|in:usage,waste,adjustment_out',
            'items.*.notes' => 'nullable|string|max:500',
            'transaction_date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['items'] as $item) {
                $ingredient = Ingredient::findOrFail($item['ingredient_id']);

                // ตรวจสอบสต็อคเพียงพอ
                if ($ingredient->current_stock < $item['quantity']) {
                    throw new \Exception("สต็อค {$ingredient->name} ไม่เพียงพอ (คงเหลือ: {$ingredient->current_stock})");
                }

                // สร้าง transaction
                InventoryTransaction::create([
                    'ingredient_id' => $item['ingredient_id'],
                    'type' => $item['type'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $ingredient->cost_per_unit,
                    'transaction_date' => $validated['transaction_date'],
                    'notes' => $item['notes'] ?? null,
                ]);

                // หักสต็อค
                $ingredient->decrement('current_stock', $item['quantity']);
            }
        });

        return redirect()->route('stock.index')->with('success', 'เบิกสินค้าออกจากสต็อคเรียบร้อยแล้ว');
    }

    /**
     * ปรับปรุงสต็อค (Stock Adjustment)
     */
    public function adjustmentCreate()
    {
        $ingredients = Ingredient::with('supplier')->orderBy('name')->get();

        return Inertia::render('Stock/Adjustment', [
            'ingredients' => $ingredients,
        ]);
    }

    /**
     * บันทึกการปรับปรุงสต็อค
     */
    public function adjustmentStore(Request $request)
    {
        $validated = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'actual_stock' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'transaction_date' => 'required|date',
        ]);

        DB::transaction(function () use ($validated) {
            $ingredient = Ingredient::findOrFail($validated['ingredient_id']);
            $difference = $validated['actual_stock'] - $ingredient->current_stock;

            if ($difference != 0) {
                InventoryTransaction::create([
                    'ingredient_id' => $validated['ingredient_id'],
                    'type' => $difference > 0 ? 'adjustment_in' : 'adjustment_out',
                    'quantity' => abs($difference),
                    'unit_cost' => $ingredient->cost_per_unit,
                    'transaction_date' => $validated['transaction_date'],
                    'notes' => 'ปรับปรุงสต็อค: ' . ($validated['notes'] ?? 'ตรวจนับสต็อค'),
                ]);

                $ingredient->update(['current_stock' => $validated['actual_stock']]);
            }
        });

        return redirect()->route('stock.index')->with('success', 'ปรับปรุงสต็อคเรียบร้อยแล้ว');
    }

    /**
     * ประวัติการเคลื่อนไหวสต็อค
     */
    public function history(Request $request)
    {
        $query = InventoryTransaction::with('ingredient');

        // Filter by ingredient
        if ($request->filled('ingredient_id')) {
            $query->where('ingredient_id', $request->ingredient_id);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->latest('transaction_date')
            ->paginate(50)
            ->withQueryString();

        $ingredients = Ingredient::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Stock/History', [
            'transactions' => $transactions,
            'ingredients' => $ingredients,
            'filters' => $request->only(['ingredient_id', 'type', 'date_from', 'date_to']),
            'transactionTypes' => [
                'purchase' => 'ซื้อเข้า',
                'usage' => 'เบิกใช้',
                'waste' => 'เสียหาย',
                'adjustment_in' => 'ปรับเพิ่ม',
                'adjustment_out' => 'ปรับลด',
            ],
        ]);
    }

    /**
     * รายงานสต็อค
     */
    public function report(Request $request)
    {
        // สต็อคคงเหลือพร้อมมูลค่า
        $stockSummary = Ingredient::with('supplier')
            ->select('ingredients.*')
            ->selectRaw('current_stock * cost_per_unit as stock_value')
            ->orderBy('name')
            ->get();

        // สรุปตามซัพพลายเออร์
        $bySupplier = Ingredient::with('supplier')
            ->select('supplier_id')
            ->selectRaw('COUNT(*) as item_count')
            ->selectRaw('SUM(current_stock * cost_per_unit) as total_value')
            ->groupBy('supplier_id')
            ->get();

        // สรุปการเคลื่อนไหว
        $movementSummary = InventoryTransaction::select('type')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(quantity * unit_cost) as total_value')
            ->when($request->filled('date_from'), fn($q) => $q->whereDate('transaction_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn($q) => $q->whereDate('transaction_date', '<=', $request->date_to))
            ->groupBy('type')
            ->get();

        return Inertia::render('Stock/Report', [
            'stockSummary' => $stockSummary,
            'bySupplier' => $bySupplier,
            'movementSummary' => $movementSummary,
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }
}
