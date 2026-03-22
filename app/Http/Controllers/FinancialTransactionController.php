<?php

namespace App\Http\Controllers;

use App\Models\FinancialCategory;
use App\Models\FinancialTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinancialTransactionController extends Controller
{
    public function index(Request $request)
    {
        // Default to current month if no date range provided
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $query = FinancialTransaction::with(['category', 'user'])
            ->latest('transaction_date');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Always apply date range (defaults to current month)
        $query->dateBetween($startDate, $endDate);

        $transactions = $query->paginate(20)->withQueryString();

        // Calculate summary for the same date range
        $summaryQuery = FinancialTransaction::query()
            ->dateBetween($startDate, $endDate);

        $totalIncome = (clone $summaryQuery)->income()->sum('amount');
        $totalExpense = (clone $summaryQuery)->expense()->sum('amount');

        return Inertia::render('Finance/Index', [
            'transactions' => $transactions,
            'categories' => FinancialCategory::active()->get(),
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'balance' => $totalIncome - $totalExpense,
            ],
            'filters' => [
                'type' => $request->input('type'),
                'category_id' => $request->input('category_id'),
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Finance/Create', [
            'categories' => FinancialCategory::active()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:financial_categories,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|in:cash,transfer,credit_card,cheque,other',
        ]);

        $validated['user_id'] = auth()->id();

        FinancialTransaction::create($validated);

        return redirect()->route('finance.index')
            ->with('success', 'บันทึกรายการสำเร็จ');
    }

    public function edit(FinancialTransaction $financialTransaction)
    {
        return Inertia::render('Finance/Edit', [
            'transaction' => $financialTransaction->load('category'),
            'categories' => FinancialCategory::active()->get(),
        ]);
    }

    public function update(Request $request, FinancialTransaction $financialTransaction)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:financial_categories,id',
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|in:cash,transfer,credit_card,cheque,other',
        ]);

        $financialTransaction->update($validated);

        return redirect()->route('finance.index')
            ->with('success', 'อัพเดทรายการสำเร็จ');
    }

    public function destroy(FinancialTransaction $financialTransaction)
    {
        $financialTransaction->delete();

        return redirect()->route('finance.index')
            ->with('success', 'ลบรายการสำเร็จ');
    }

    /**
     * หน้ารายงานสรุป
     */
    public function report(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        // สรุปตามหมวดหมู่
        $incomeByCategory = FinancialTransaction::income()
            ->dateBetween($startDate, $endDate)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        $expenseByCategory = FinancialTransaction::expense()
            ->dateBetween($startDate, $endDate)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->with('category')
            ->get();

        // รายละเอียดตามคำอธิบาย แยกต่อหมวดหมู่ (แปลงเป็น array ทันทีเพื่อหลีกเลี่ยง lazy-load N+1)
        $incomeDescriptionBreakdown = FinancialTransaction::income()
            ->dateBetween($startDate, $endDate)
            ->selectRaw('category_id, description, SUM(amount) as total, COUNT(*) as item_count')
            ->groupBy('category_id', 'description')
            ->orderBy('category_id')
            ->orderByRaw('SUM(amount) DESC')
            ->get()
            ->groupBy('category_id')
            ->map(fn($items) => $items->map(fn($t) => [
                'description' => $t->description,
                'total'       => $t->total,
                'count'       => $t->item_count,
            ])->values());

        $expenseDescriptionBreakdown = FinancialTransaction::expense()
            ->dateBetween($startDate, $endDate)
            ->selectRaw('category_id, description, SUM(amount) as total, COUNT(*) as item_count')
            ->groupBy('category_id', 'description')
            ->orderBy('category_id')
            ->orderByRaw('SUM(amount) DESC')
            ->get()
            ->groupBy('category_id')
            ->map(fn($items) => $items->map(fn($t) => [
                'description' => $t->description,
                'total'       => $t->total,
                'count'       => $t->item_count,
            ])->values());

        $incomeByCategory = $incomeByCategory->map(fn($item) => [
            'category_id' => $item->category_id,
            'total'       => $item->total,
            'category'    => $item->category,
            'details'     => $incomeDescriptionBreakdown->get($item->category_id, collect())->values(),
        ]);

        $expenseByCategory = $expenseByCategory->map(fn($item) => [
            'category_id' => $item->category_id,
            'total'       => $item->total,
            'category'    => $item->category,
            'details'     => $expenseDescriptionBreakdown->get($item->category_id, collect())->values(),
        ]);

        // สรุปรายวัน
        $dailySummary = FinancialTransaction::dateBetween($startDate, $endDate)
            ->selectRaw('transaction_date, type, SUM(amount) as total')
            ->groupBy('transaction_date', 'type')
            ->orderBy('transaction_date')
            ->get()
            ->groupBy('transaction_date');

        $totalIncome = FinancialTransaction::income()->dateBetween($startDate, $endDate)->sum('amount');
        $totalExpense = FinancialTransaction::expense()->dateBetween($startDate, $endDate)->sum('amount');

        return Inertia::render('Finance/Report', [
            'income_by_category' => $incomeByCategory,
            'expense_by_category' => $expenseByCategory,
            'daily_summary' => $dailySummary,
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'balance' => $totalIncome - $totalExpense,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * ดึงรายละเอียดที่เคยเพิ่มทั้งหมดตามหมวดหมู่
     */
    public function recentDescriptions(Request $request)
    {
        $categoryId = $request->get('category_id');
        $type = $request->get('type');

        $query = FinancialTransaction::select('description')
            ->selectRaw('COUNT(*) as usage_count')
            ->selectRaw('MAX(created_at) as last_used')
            ->groupBy('description')
            ->orderByDesc('last_used');

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($type) {
            $query->where('type', $type);
        }

        return response()->json($query->get());
    }
}
