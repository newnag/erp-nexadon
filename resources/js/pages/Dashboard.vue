<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { index as ingredientsIndex } from '@/routes/ingredients';
import { index as recipesIndex, create as recipesCreate } from '@/routes/recipes';
import { index as stockIndex } from '@/routes/stock';
import { index as financeIndex, create as financeCreate } from '@/routes/finance';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
    Package, 
    ChefHat, 
    Truck, 
    TrendingUp, 
    TrendingDown, 
    DollarSign,
    AlertTriangle,
    Plus,
    ArrowRight,
    Wallet,
    BarChart3,
    Clock
} from 'lucide-vue-next';

interface Supplier {
    id: number;
    name: string;
}

interface Ingredient {
    id: number;
    name: string;
    current_stock: number;
    reorder_point: number;
    purchase_unit: string;
    supplier?: Supplier;
}

interface Category {
    id: number;
    name: string;
    color?: string;
}

interface User {
    id: number;
    name: string;
}

interface Transaction {
    id: number;
    type: 'income' | 'expense';
    amount: number;
    description: string;
    transaction_date: string;
    category?: Category;
    user?: User;
}

interface Recipe {
    id: number;
    name: string;
    selling_price: number;
    total_cost: number;
    created_at: string;
}

interface Props {
    stats: {
        totalIngredients: number;
        totalRecipes: number;
        totalSuppliers: number;
        incomeThisMonth: number;
        expenseThisMonth: number;
        profitThisMonth: number;
    };
    lowStockIngredients: Ingredient[];
    recentTransactions: Transaction[];
    recentRecipes: Recipe[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'แดชบอร์ด',
        href: dashboard().url,
    },
];

const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('th-TH', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="แดชบอร์ด" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 p-3 sm:p-4 md:p-6">
            <!-- Header with Quick Actions - Responsive -->
            <div class="flex flex-col gap-3 sm:gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight">แดชบอร์ด</h1>
                    <p class="text-sm text-muted-foreground">ภาพรวมระบบจัดการธุรกิจ</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Button as-child size="sm" variant="outline" class="flex-1 sm:flex-none">
                        <Link :href="financeCreate().url">
                            <Plus class="mr-1 h-4 w-4" />
                            <span class="hidden xs:inline">บันทึก</span>รายรับ/จ่าย
                        </Link>
                    </Button>
                    <Button as-child size="sm" class="flex-1 sm:flex-none">
                        <Link :href="recipesCreate().url">
                            <Plus class="mr-1 h-4 w-4" />
                            <span class="hidden xs:inline">สร้าง</span>สูตรใหม่
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Stats Cards - Responsive Grid -->
            <div class="grid gap-3 sm:gap-4 grid-cols-2 lg:grid-cols-4">
                <!-- Total Ingredients -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 sm:pb-2 p-3 sm:p-6">
                        <CardTitle class="text-xs sm:text-sm font-medium">วัตถุดิบทั้งหมด</CardTitle>
                        <Package class="h-3 w-3 sm:h-4 sm:w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent class="p-3 sm:p-6 pt-0">
                        <div class="text-lg sm:text-2xl font-bold">{{ stats.totalIngredients }}</div>
                        <Link :href="ingredientsIndex().url" class="text-xs text-muted-foreground hover:text-primary flex items-center gap-1">
                            ดูทั้งหมด <ArrowRight class="h-3 w-3" />
                        </Link>
                    </CardContent>
                </Card>

                <!-- Total Recipes -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 sm:pb-2 p-3 sm:p-6">
                        <CardTitle class="text-xs sm:text-sm font-medium">สูตรอาหาร</CardTitle>
                        <ChefHat class="h-3 w-3 sm:h-4 sm:w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent class="p-3 sm:p-6 pt-0">
                        <div class="text-lg sm:text-2xl font-bold">{{ stats.totalRecipes }}</div>
                        <Link :href="recipesIndex().url" class="text-xs text-muted-foreground hover:text-primary flex items-center gap-1">
                            ดูทั้งหมด <ArrowRight class="h-3 w-3" />
                        </Link>
                    </CardContent>
                </Card>

                <!-- Income This Month -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 sm:pb-2 p-3 sm:p-6">
                        <CardTitle class="text-xs sm:text-sm font-medium">รายรับ<span class="hidden sm:inline">เดือนนี้</span></CardTitle>
                        <TrendingUp class="h-3 w-3 sm:h-4 sm:w-4 text-green-500" />
                    </CardHeader>
                    <CardContent class="p-3 sm:p-6 pt-0">
                        <div class="text-lg sm:text-2xl font-bold text-green-600 truncate">{{ formatCurrency(stats.incomeThisMonth) }}</div>
                        <Link :href="financeIndex().url" class="text-xs text-muted-foreground hover:text-primary flex items-center gap-1">
                            ดูรายละเอียด <ArrowRight class="h-3 w-3" />
                        </Link>
                    </CardContent>
                </Card>

                <!-- Expense This Month -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-1 sm:pb-2 p-3 sm:p-6">
                        <CardTitle class="text-xs sm:text-sm font-medium">รายจ่าย<span class="hidden sm:inline">เดือนนี้</span></CardTitle>
                        <TrendingDown class="h-3 w-3 sm:h-4 sm:w-4 text-red-500" />
                    </CardHeader>
                    <CardContent class="p-3 sm:p-6 pt-0">
                        <div class="text-lg sm:text-2xl font-bold text-red-600 truncate">{{ formatCurrency(stats.expenseThisMonth) }}</div>
                        <Link :href="financeIndex().url" class="text-xs text-muted-foreground hover:text-primary flex items-center gap-1">
                            ดูรายละเอียด <ArrowRight class="h-3 w-3" />
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <!-- Profit Summary Card - Responsive -->
            <Card :class="stats.profitThisMonth >= 0 ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950/30' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/30'">
                <CardContent class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4 py-3 sm:py-4 px-3 sm:px-6">
                    <div class="flex items-center gap-3 sm:gap-4">
                        <div :class="['rounded-full p-2 sm:p-3', stats.profitThisMonth >= 0 ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900']">
                            <Wallet :class="['h-4 w-4 sm:h-6 sm:w-6', stats.profitThisMonth >= 0 ? 'text-green-600' : 'text-red-600']" />
                        </div>
                        <div>
                            <p class="text-xs sm:text-sm text-muted-foreground">กำไร/ขาดทุนสุทธิ เดือนนี้</p>
                            <p :class="['text-xl sm:text-3xl font-bold truncate', stats.profitThisMonth >= 0 ? 'text-green-600' : 'text-red-600']">
                                {{ formatCurrency(stats.profitThisMonth) }}
                            </p>
                        </div>
                    </div>
                    <Button as-child variant="outline" size="sm" class="w-full sm:w-auto">
                        <Link :href="financeIndex().url">
                            <BarChart3 class="mr-2 h-4 w-4" />
                            ดูรายงาน
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <!-- Main Content Grid - Responsive -->
            <div class="grid gap-4 sm:gap-6 lg:grid-cols-2">
                <!-- Low Stock Alert -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <AlertTriangle class="h-5 w-5 text-amber-500" />
                                <CardTitle>วัตถุดิบใกล้หมด</CardTitle>
                            </div>
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="stockIndex().url">
                                    ดูทั้งหมด
                                </Link>
                            </Button>
                        </div>
                        <CardDescription>วัตถุดิบที่ต้องสั่งซื้อเพิ่ม</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="lowStockIngredients.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                            <Package class="h-12 w-12 text-muted-foreground/50" />
                            <p class="mt-2 text-sm text-muted-foreground">ไม่มีวัตถุดิบที่ต้องสั่งซื้อเพิ่ม</p>
                        </div>
                        <div v-else class="space-y-3">
                            <div 
                                v-for="ingredient in lowStockIngredients" 
                                :key="ingredient.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div>
                                    <p class="font-medium">{{ ingredient.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ ingredient.supplier?.name ?? 'ไม่ระบุซัพพลายเออร์' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <Badge variant="destructive" class="mb-1">
                                        {{ ingredient.current_stock }} {{ ingredient.purchase_unit }}
                                    </Badge>
                                    <p class="text-xs text-muted-foreground">
                                        ขั้นต่ำ: {{ ingredient.reorder_point }} {{ ingredient.purchase_unit }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Transactions -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <DollarSign class="h-5 w-5 text-blue-500" />
                                <CardTitle>รายการเงินล่าสุด</CardTitle>
                            </div>
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="financeIndex().url">
                                    ดูทั้งหมด
                                </Link>
                            </Button>
                        </div>
                        <CardDescription>5 รายการล่าสุด</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentTransactions.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                            <Wallet class="h-12 w-12 text-muted-foreground/50" />
                            <p class="mt-2 text-sm text-muted-foreground">ยังไม่มีรายการ</p>
                            <Button as-child size="sm" class="mt-4">
                                <Link :href="financeCreate().url">
                                    <Plus class="mr-1 h-4 w-4" />
                                    เพิ่มรายการแรก
                                </Link>
                            </Button>
                        </div>
                        <div v-else class="space-y-3">
                            <div 
                                v-for="transaction in recentTransactions" 
                                :key="transaction.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div :class="['rounded-full p-2', transaction.type === 'income' ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900']">
                                        <TrendingUp v-if="transaction.type === 'income'" class="h-4 w-4 text-green-600" />
                                        <TrendingDown v-else class="h-4 w-4 text-red-600" />
                                    </div>
                                    <div>
                                        <p class="font-medium line-clamp-1">{{ transaction.description }}</p>
                                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                            <span>{{ formatDate(transaction.transaction_date) }}</span>
                                            <span v-if="transaction.category" 
                                                class="rounded px-1.5 py-0.5" 
                                                :style="{ backgroundColor: transaction.category.color + '20', color: transaction.category.color }"
                                            >
                                                {{ transaction.category.name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <p :class="['font-semibold whitespace-nowrap', transaction.type === 'income' ? 'text-green-600' : 'text-red-600']">
                                    {{ transaction.type === 'income' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Recent Recipes -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <ChefHat class="h-5 w-5 text-purple-500" />
                                <CardTitle>สูตรอาหารล่าสุด</CardTitle>
                            </div>
                            <Button as-child variant="ghost" size="sm">
                                <Link :href="recipesIndex().url">
                                    ดูทั้งหมด
                                </Link>
                            </Button>
                        </div>
                        <CardDescription>5 สูตรล่าสุดที่เพิ่มเข้าระบบ</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentRecipes.length === 0" class="flex flex-col items-center justify-center py-8 text-center">
                            <ChefHat class="h-12 w-12 text-muted-foreground/50" />
                            <p class="mt-2 text-sm text-muted-foreground">ยังไม่มีสูตรอาหาร</p>
                            <Button as-child size="sm" class="mt-4">
                                <Link :href="recipesCreate().url">
                                    <Plus class="mr-1 h-4 w-4" />
                                    สร้างสูตรแรก
                                </Link>
                            </Button>
                        </div>
                        <div v-else class="space-y-3">
                            <div 
                                v-for="recipe in recentRecipes" 
                                :key="recipe.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <div>
                                    <p class="font-medium">{{ recipe.name }}</p>
                                    <p class="text-xs text-muted-foreground flex items-center gap-1">
                                        <Clock class="h-3 w-3" />
                                        {{ formatDate(recipe.created_at) }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold">{{ formatCurrency(recipe.selling_price) }}</p>
                                    <p class="text-xs text-muted-foreground">ต้นทุน: {{ formatCurrency(recipe.total_cost) }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Quick Actions - Responsive -->
                <Card>
                    <CardHeader class="p-4 sm:p-6">
                        <CardTitle class="text-base sm:text-lg">เมนูลัด</CardTitle>
                        <CardDescription class="text-xs sm:text-sm">เข้าถึงฟังก์ชันหลักได้รวดเร็ว</CardDescription>
                    </CardHeader>
                    <CardContent class="p-4 sm:p-6 pt-0">
                        <div class="grid grid-cols-2 gap-2 sm:gap-3">
                            <Button as-child variant="outline" class="h-auto flex-col gap-1 sm:gap-2 py-3 sm:py-4">
                                <Link :href="ingredientsIndex().url">
                                    <Package class="h-5 w-5 sm:h-6 sm:w-6" />
                                    <span class="text-xs sm:text-sm">วัตถุดิบ</span>
                                </Link>
                            </Button>
                            <Button as-child variant="outline" class="h-auto flex-col gap-1 sm:gap-2 py-3 sm:py-4">
                                <Link :href="stockIndex().url">
                                    <Truck class="h-5 w-5 sm:h-6 sm:w-6" />
                                    <span class="text-xs sm:text-sm">สต็อค</span>
                                </Link>
                            </Button>
                            <Button as-child variant="outline" class="h-auto flex-col gap-1 sm:gap-2 py-3 sm:py-4">
                                <Link :href="recipesIndex().url">
                                    <ChefHat class="h-5 w-5 sm:h-6 sm:w-6" />
                                    <span class="text-xs sm:text-sm">สูตรอาหาร</span>
                                </Link>
                            </Button>
                            <Button as-child variant="outline" class="h-auto flex-col gap-1 sm:gap-2 py-3 sm:py-4">
                                <Link :href="financeIndex().url">
                                    <Wallet class="h-5 w-5 sm:h-6 sm:w-6" />
                                    <span class="text-xs sm:text-sm">การเงิน</span>
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
