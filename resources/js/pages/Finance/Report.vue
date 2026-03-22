<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, TrendingUp, TrendingDown, Wallet, BarChart3 } from 'lucide-vue-next';
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
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 p-3 sm:p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div class="flex items-center gap-3">
                    <Link href="/finance">
                        <Button variant="ghost" size="icon" class="h-8 w-8 sm:h-10 sm:w-10">
                            <ArrowLeft class="w-4 h-4 sm:w-5 sm:h-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-lg sm:text-2xl font-bold flex items-center gap-2">
                            <BarChart3 class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600" />
                            รายงานการเงิน
                        </h1>
                        <p class="text-sm text-muted-foreground">สรุปรายรับ-รายจ่ายตามช่วงเวลา</p>
                    </div>
                </div>
            </div>

            <!-- Date Filter -->
            <Card>
                <CardContent class="pt-6">
                    <form @submit.prevent="applyFilters" class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 sm:gap-4">
                        <div class="space-y-2">
                            <Label>ตั้งแต่วันที่</Label>
                            <Input type="date" v-model="filterForm.start_date" />
                        </div>
                        <div class="space-y-2">
                            <Label>ถึงวันที่</Label>
                            <Input type="date" v-model="filterForm.end_date" />
                        </div>
                        <Button type="submit">ดูรายงาน</Button>
                    </form>
                </CardContent>
            </Card>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card class="border-green-200 dark:border-green-800/50">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">รายรับรวม</CardTitle>
                        <TrendingUp class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl sm:text-3xl font-bold text-green-600 dark:text-green-400">{{ formatCurrency(summary.total_income) }}</p>
                    </CardContent>
                </Card>
                <Card class="border-red-200 dark:border-red-800/50">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">รายจ่ายรวม</CardTitle>
                        <TrendingDown class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-2xl sm:text-3xl font-bold text-red-600 dark:text-red-400">{{ formatCurrency(summary.total_expense) }}</p>
                    </CardContent>
                </Card>
                <Card :class="summary.balance >= 0 ? 'border-blue-200 dark:border-blue-800/50' : 'border-orange-200 dark:border-orange-800/50'">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">คงเหลือ</CardTitle>
                        <Wallet :class="['h-4 w-4', summary.balance >= 0 ? 'text-blue-600' : 'text-orange-600']" />
                    </CardHeader>
                    <CardContent>
                        <p :class="['text-2xl sm:text-3xl font-bold', summary.balance >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400']">
                            {{ formatCurrency(summary.balance) }}
                        </p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Income by Category -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-green-600 dark:text-green-400">รายรับตามหมวดหมู่</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-if="income_by_category.length > 0">
                            <div v-for="item in income_by_category" :key="item.category_id">
                                <button
                                    type="button"
                                    class="w-full flex items-center gap-3 px-6 py-3 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors text-left"
                                    @click="toggleCategory('income_' + item.category_id)"
                                >
                                    <div class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: item.category?.color }"></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-sm font-semibold truncate">{{ item.category?.name }}</span>
                                            <span class="text-sm font-bold text-green-600 dark:text-green-400 ml-2 shrink-0">{{ formatCurrency(Number(item.total)) }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-muted rounded-full h-1.5">
                                                <div class="h-1.5 rounded-full" :style="{ width: getPercentage(Number(item.total), incomeTotal) + '%', backgroundColor: item.category?.color }"></div>
                                            </div>
                                            <span class="text-xs text-muted-foreground w-9 text-right shrink-0">{{ getPercentage(Number(item.total), incomeTotal) }}%</span>
                                        </div>
                                    </div>
                                    <span class="text-muted-foreground text-xs shrink-0 ml-1">{{ isExpanded('income_' + item.category_id) ? '▲' : '▼' }}</span>
                                </button>
                                <div v-if="isExpanded('income_' + item.category_id)" class="bg-green-50/60 dark:bg-green-900/10 border-t border-green-100 dark:border-green-800">
                                    <div v-if="item.details && item.details.length > 0">
                                        <div v-for="detail in item.details" :key="detail.description" class="flex items-center justify-between px-8 py-2 border-b border-green-100/60 dark:border-green-800/40 last:border-0">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-400 shrink-0"></span>
                                                <span class="text-sm truncate">{{ detail.description }}</span>
                                                <span class="text-xs text-muted-foreground shrink-0">({{ detail.count }} รายการ)</span>
                                            </div>
                                            <span class="text-sm font-medium text-green-600 dark:text-green-400 ml-4 shrink-0">{{ formatCurrency(Number(detail.total)) }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="px-8 py-2 text-xs text-muted-foreground">ไม่มีรายละเอียด</div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center text-muted-foreground py-8">ไม่มีรายรับในช่วงเวลานี้</div>
                    </CardContent>
                </Card>

                <!-- Expense by Category -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-red-600 dark:text-red-400">รายจ่ายตามหมวดหมู่</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <div v-if="expense_by_category.length > 0">
                            <div v-for="item in expense_by_category" :key="item.category_id">
                                <button
                                    type="button"
                                    class="w-full flex items-center gap-3 px-6 py-3 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left"
                                    @click="toggleCategory('expense_' + item.category_id)"
                                >
                                    <div class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: item.category?.color }"></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-sm font-semibold truncate">{{ item.category?.name }}</span>
                                            <span class="text-sm font-bold text-red-600 dark:text-red-400 ml-2 shrink-0">{{ formatCurrency(Number(item.total)) }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 bg-muted rounded-full h-1.5">
                                                <div class="h-1.5 rounded-full" :style="{ width: getPercentage(Number(item.total), expenseTotal) + '%', backgroundColor: item.category?.color }"></div>
                                            </div>
                                            <span class="text-xs text-muted-foreground w-9 text-right shrink-0">{{ getPercentage(Number(item.total), expenseTotal) }}%</span>
                                        </div>
                                    </div>
                                    <span class="text-muted-foreground text-xs shrink-0 ml-1">{{ isExpanded('expense_' + item.category_id) ? '▲' : '▼' }}</span>
                                </button>
                                <div v-if="isExpanded('expense_' + item.category_id)" class="bg-red-50/60 dark:bg-red-900/10 border-t border-red-100 dark:border-red-800">
                                    <div v-if="item.details && item.details.length > 0">
                                        <div v-for="detail in item.details" :key="detail.description" class="flex items-center justify-between px-8 py-2 border-b border-red-100/60 dark:border-red-800/40 last:border-0">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-400 shrink-0"></span>
                                                <span class="text-sm truncate">{{ detail.description }}</span>
                                                <span class="text-xs text-muted-foreground shrink-0">({{ detail.count }} รายการ)</span>
                                            </div>
                                            <span class="text-sm font-medium text-red-600 dark:text-red-400 ml-4 shrink-0">{{ formatCurrency(Number(detail.total)) }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="px-8 py-2 text-xs text-muted-foreground">ไม่มีรายละเอียด</div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center text-muted-foreground py-8">ไม่มีรายจ่ายในช่วงเวลานี้</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Daily Chart -->
            <Card>
                <CardHeader>
                    <CardTitle>รายรับ-รายจ่ายรายวัน</CardTitle>
                </CardHeader>
                <CardContent>
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
                                <div class="text-xs text-muted-foreground text-center" style="writing-mode: vertical-lr; transform: rotate(180deg); height: 50px;">
                                    {{ new Date(day.date).toLocaleDateString('th-TH', { day: 'numeric', month: 'short' }) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 mt-4 justify-center">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-green-500 rounded"></div>
                                <span class="text-sm text-muted-foreground">รายรับ</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 bg-red-500 rounded"></div>
                                <span class="text-sm text-muted-foreground">รายจ่าย</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-muted-foreground py-8">ไม่มีข้อมูลในช่วงเวลานี้</div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
