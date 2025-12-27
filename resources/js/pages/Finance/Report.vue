<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Category {
    id: number;
    name: string;
    type: 'income' | 'expense';
    color: string;
}

interface CategorySummary {
    category_id: number;
    total: number;
    category: Category;
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
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        รายงานการเงิน
                    </h2>
                    <Link href="/finance" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        ← กลับ
                    </Link>
                </div>

                <!-- Date Filter -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <form @submit.prevent="applyFilters" class="flex items-end gap-4">
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
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-green-600 mb-4">รายรับตามหมวดหมู่</h3>
                        <div v-if="income_by_category.length > 0" class="space-y-3">
                            <div v-for="item in income_by_category" :key="item.category_id" class="flex items-center gap-3">
                                <div 
                                    class="w-3 h-3 rounded-full flex-shrink-0" 
                                    :style="{ backgroundColor: item.category?.color }"
                                ></div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium">{{ item.category?.name }}</span>
                                        <span class="text-sm text-gray-600">{{ formatCurrency(Number(item.total)) }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="h-2 rounded-full" 
                                            :style="{ 
                                                width: getPercentage(Number(item.total), incomeTotal) + '%',
                                                backgroundColor: item.category?.color 
                                            }"
                                        ></div>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-500 w-12 text-right">
                                    {{ getPercentage(Number(item.total), incomeTotal) }}%
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-center text-gray-500 py-8">
                            ไม่มีรายรับในช่วงเวลานี้
                        </div>
                    </div>

                    <!-- Expense by Category -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-red-600 mb-4">รายจ่ายตามหมวดหมู่</h3>
                        <div v-if="expense_by_category.length > 0" class="space-y-3">
                            <div v-for="item in expense_by_category" :key="item.category_id" class="flex items-center gap-3">
                                <div 
                                    class="w-3 h-3 rounded-full flex-shrink-0" 
                                    :style="{ backgroundColor: item.category?.color }"
                                ></div>
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-sm font-medium">{{ item.category?.name }}</span>
                                        <span class="text-sm text-gray-600">{{ formatCurrency(Number(item.total)) }}</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div 
                                            class="h-2 rounded-full" 
                                            :style="{ 
                                                width: getPercentage(Number(item.total), expenseTotal) + '%',
                                                backgroundColor: item.category?.color 
                                            }"
                                        ></div>
                                    </div>
                                </div>
                                <span class="text-xs text-gray-500 w-12 text-right">
                                    {{ getPercentage(Number(item.total), expenseTotal) }}%
                                </span>
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
