<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Category {
    id: number;
    name: string;
    type: 'income' | 'expense';
    color: string;
}

interface DescriptionDetail {
    description: string;
    total: number;
    count: number;
}

interface CategorySummary {
    category_id: number;
    total: number;
    category: Category;
    details: DescriptionDetail[];
}

interface DailySummary {
    [date: string]: Array<{
        type: 'income' | 'expense';
        total: number;
    }>;
}

const props = defineProps<{
    income_by_category: CategorySummary[];
    expense_by_category: CategorySummary[];
    daily_summary: DailySummary;
    summary: {
        total_income: number;
        total_expense: number;
        balance: number;
    };
    filters: {
        start_date: string;
        end_date: string;
    };
}>();

// Track which category rows are expanded (key = "income_1" or "expense_2")
const expandedCategories = ref<Set<string>>(new Set());

const toggleCategory = (key: string) => {
    if (expandedCategories.value.has(key)) {
        expandedCategories.value.delete(key);
    } else {
        expandedCategories.value.add(key);
    }
};

const isExpanded = (key: string) => expandedCategories.value.has(key);

const breadcrumbs = [
    {
        title: 'การเงิน',
        href: '/finance',
    },
    {
        title: 'รายงาน',
        href: '/finance/report',
    },
];

const filterForm = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const applyFilters = () => {
    filterForm.get('/finance/report', {
        preserveState: true,
    });
};

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB',
    }).format(amount);
};



// Calculate percentages for charts
const incomeTotal = computed(() => props.income_by_category.reduce((sum, item) => sum + Number(item.total), 0));
const expenseTotal = computed(() => props.expense_by_category.reduce((sum, item) => sum + Number(item.total), 0));

const getPercentage = (amount: number, total: number) => {
    if (total === 0) return 0;
    return Math.round((amount / total) * 100);
};

// Daily data for chart
const dailyData = computed(() => {
    const dates = Object.keys(props.daily_summary).sort();
    return dates.map(date => {
        const items = props.daily_summary[date] || [];
        const income = items.find(i => i.type === 'income')?.total || 0;
        const expense = items.find(i => i.type === 'expense')?.total || 0;
        return { date, income: Number(income), expense: Number(expense) };
    });
});

// Max value for chart scaling
const maxDailyValue = computed(() => {
    let max = 0;
    dailyData.value.forEach(d => {
        max = Math.max(max, d.income, d.expense);
    });
    return max || 1;
});
</script>

<template>
    <Head title="รายงานการเงิน" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-4 sm:py-6">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 sm:mb-6">
                    <h2 class="font-semibold text-lg sm:text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        รายงานการเงิน
                    </h2>
                    <Link href="/finance" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                        ← กลับ
                    </Link>
                </div>

                <!-- Date Filter -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 sm:p-4 mb-4 sm:mb-6">
                    <form @submit.prevent="applyFilters" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 sm:gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ตั้งแต่วันที่</label>
                            <input type="date" v-model="filterForm.start_date" class="rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ถึงวันที่</label>
                            <input type="date" v-model="filterForm.end_date" class="rounded-md border-gray-300 shadow-sm">
                        </div>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            ดูรายงาน
                        </button>
                    </form>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                        <h3 class="text-sm font-medium text-green-600">รายรับรวม</h3>
                        <p class="text-3xl font-bold text-green-700">{{ formatCurrency(summary.total_income) }}</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-6 border border-red-200">
                        <h3 class="text-sm font-medium text-red-600">รายจ่ายรวม</h3>
                        <p class="text-3xl font-bold text-red-700">{{ formatCurrency(summary.total_expense) }}</p>
                    </div>
                    <div :class="[
                        'rounded-lg p-6 border',
                        summary.balance >= 0 
                            ? 'bg-blue-50 border-blue-200' 
                            : 'bg-orange-50 border-orange-200'
                    ]">
                        <h3 :class="[
                            'text-sm font-medium',
                            summary.balance >= 0 ? 'text-blue-600' : 'text-orange-600'
                        ]">คงเหลือ</h3>
                        <p :class="[
                            'text-3xl font-bold',
                            summary.balance >= 0 ? 'text-blue-700' : 'text-orange-700'
                        ]">{{ formatCurrency(summary.balance) }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Income by Category -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-green-600 dark:text-green-400">รายรับตามหมวดหมู่</h3>
                        </div>
                        <div v-if="income_by_category.length > 0">
                            <div v-for="item in income_by_category" :key="item.category_id">
                                <!-- Category row -->
                                <button
                                    type="button"
                                    class="w-full flex items-center gap-3 px-6 py-3 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors text-left"
                                    @click="toggleCategory('income_' + item.category_id)"
                                >
                                    <div
                                        class="w-3 h-3 rounded-full shrink-0"
                                        :style="{ backgroundColor: item.category?.color }"
                                    ></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">
                                                {{ item.category?.name }}
                                            </span>
                                            <span class="text-sm font-bold text-green-600 dark:text-green-400 ml-2 shrink-0">
                                                {{ formatCurrency(Number(item.total)) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                                <div
                                                    class="h-1.5 rounded-full"
                                                    :style="{
                                                        width: getPercentage(Number(item.total), incomeTotal) + '%',
                                                        backgroundColor: item.category?.color,
                                                    }"
                                                ></div>
                                            </div>
                                            <span class="text-xs text-gray-400 w-9 text-right shrink-0">
                                                {{ getPercentage(Number(item.total), incomeTotal) }}%
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-gray-400 dark:text-gray-500 text-xs shrink-0 ml-1">
                                        {{ isExpanded('income_' + item.category_id) ? '▲' : '▼' }}
                                    </span>
                                </button>
                                <!-- Description detail rows -->
                                <div v-if="isExpanded('income_' + item.category_id)" class="bg-green-50/60 dark:bg-green-900/10 border-t border-green-100 dark:border-green-800">
                                    <div v-if="item.details && item.details.length > 0">
                                        <div
                                            v-for="detail in item.details"
                                            :key="detail.description"
                                            class="flex items-center justify-between px-8 py-2 border-b border-green-100/60 dark:border-green-800/40 last:border-0"
                                        >
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400 shrink-0"></span>
                                                <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ detail.description }}</span>
                                                <span class="text-xs text-gray-400 shrink-0">({{ detail.count }} รายการ)</span>
                                            </div>
                                            <span class="text-sm font-medium text-green-600 dark:text-green-400 ml-4 shrink-0">
                                                {{ formatCurrency(Number(detail.total)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-else class="px-8 py-2 text-xs text-gray-400">ไม่มีรายละเอียด</div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center text-gray-500 py-8">
                            ไม่มีรายรับในช่วงเวลานี้
                        </div>
                    </div>

                    <!-- Expense by Category -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-red-600 dark:text-red-400">รายจ่ายตามหมวดหมู่</h3>
                        </div>
                        <div v-if="expense_by_category.length > 0">
                            <div v-for="item in expense_by_category" :key="item.category_id">
                                <!-- Category row -->
                                <button
                                    type="button"
                                    class="w-full flex items-center gap-3 px-6 py-3 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left"
                                    @click="toggleCategory('expense_' + item.category_id)"
                                >
                                    <div
                                        class="w-3 h-3 rounded-full shrink-0"
                                        :style="{ backgroundColor: item.category?.color }"
                                    ></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">
                                                {{ item.category?.name }}
                                            </span>
                                            <span class="text-sm font-bold text-red-600 dark:text-red-400 ml-2 shrink-0">
                                                {{ formatCurrency(Number(item.total)) }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                                <div
                                                    class="h-1.5 rounded-full"
                                                    :style="{
                                                        width: getPercentage(Number(item.total), expenseTotal) + '%',
                                                        backgroundColor: item.category?.color,
                                                    }"
                                                ></div>
                                            </div>
                                            <span class="text-xs text-gray-400 w-9 text-right shrink-0">
                                                {{ getPercentage(Number(item.total), expenseTotal) }}%
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-gray-400 dark:text-gray-500 text-xs shrink-0 ml-1">
                                        {{ isExpanded('expense_' + item.category_id) ? '▲' : '▼' }}
                                    </span>
                                </button>
                                <!-- Description detail rows -->
                                <div v-if="isExpanded('expense_' + item.category_id)" class="bg-red-50/60 dark:bg-red-900/10 border-t border-red-100 dark:border-red-800">
                                    <div v-if="item.details && item.details.length > 0">
                                        <div
                                            v-for="detail in item.details"
                                            :key="detail.description"
                                            class="flex items-center justify-between px-8 py-2 border-b border-red-100/60 dark:border-red-800/40 last:border-0"
                                        >
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-400 shrink-0"></span>
                                                <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ detail.description }}</span>
                                                <span class="text-xs text-gray-400 shrink-0">({{ detail.count }} รายการ)</span>
                                            </div>
                                            <span class="text-sm font-medium text-red-600 dark:text-red-400 ml-4 shrink-0">
                                                {{ formatCurrency(Number(detail.total)) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div v-else class="px-8 py-2 text-xs text-gray-400">ไม่มีรายละเอียด</div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center text-gray-500 py-8">
                            ไม่มีรายจ่ายในช่วงเวลานี้
                        </div>
                    </div>
                </div>

                <!-- Daily Chart -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">รายรับ-รายจ่ายรายวัน</h3>
                    <div v-if="dailyData.length > 0" class="overflow-x-auto">
                        <div class="flex items-end gap-1 min-w-max" style="height: 200px;">
                            <div v-for="day in dailyData" :key="day.date" class="flex flex-col items-center gap-1" style="width: 60px;">
                                <div class="flex gap-1 items-end" style="height: 150px;">
                                    <div 
                                        class="w-5 bg-green-500 rounded-t"
                                        :style="{ height: (day.income / maxDailyValue * 150) + 'px' }"
                                        :title="'รายรับ: ' + formatCurrency(day.income)"
                                    ></div>
                                    <div 
                                        class="w-5 bg-red-500 rounded-t"
                                        :style="{ height: (day.expense / maxDailyValue * 150) + 'px' }"
                                        :title="'รายจ่าย: ' + formatCurrency(day.expense)"
                                    ></div>
                                </div>
                                <div class="text-xs text-gray-500 text-center" style="writing-mode: vertical-lr; transform: rotate(180deg); height: 50px;">
                                    {{ new Date(day.date).toLocaleDateString('th-TH', { day: 'numeric', month: 'short' }) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 mt-4 justify-center">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-green-500 rounded"></div>
                                <span class="text-sm text-gray-600">รายรับ</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-red-500 rounded"></div>
                                <span class="text-sm text-gray-600">รายจ่าย</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-gray-500 py-8">
                        ไม่มีข้อมูลในช่วงเวลานี้
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
