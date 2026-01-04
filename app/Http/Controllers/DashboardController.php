<?php

namespace App\Http\Controllers;

use App\Models\FinancialTransaction;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // สถิติภาพรวม
        $stats = [
            'totalIngredients' => Ingredient::count(),
            'totalRecipes' => Recipe::count(),
            'totalSuppliers' => Supplier::count(),
            'incomeThisMonth' => FinancialTransaction::where('type', 'income')
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->sum('amount'),
            'expenseThisMonth' => FinancialTransaction::where('type', 'expense')
                ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
                ->sum('amount'),
        ];
        
        $stats['profitThisMonth'] = $stats['incomeThisMonth'] - $stats['expenseThisMonth'];

        // วัตถุดิบที่ใกล้หมด
        $lowStockIngredients = Ingredient::whereColumn('current_stock', '<=', 'reorder_point')
            ->with('supplier')
            ->orderBy('current_stock')
            ->limit(5)
            ->get();

        // รายการเงินล่าสุด
        $recentTransactions = FinancialTransaction::with(['category', 'user'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // สูตรอาหารล่าสุด
        $recentRecipes = Recipe::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'lowStockIngredients' => $lowStockIngredients,
            'recentTransactions' => $recentTransactions,
            'recentRecipes' => $recentRecipes,
        ]);
    }
}
