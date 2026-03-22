<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import QuickTransactionModal from '@/components/QuickTransactionModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    TrendingUp, 
    TrendingDown, 
    Wallet, 
    BarChart3, 
    FolderOpen, 
    Plus, 
    Zap, 
    Copy, 
    Pencil, 
    Trash2,
    Search,
    X,
    FileText
} from 'lucide-vue-next';

interface Category {
    id: number;
    name: string;
    type: 'income' | 'expense';
    color: string;
}

interface Transaction {
    id: number;
    type: 'income' | 'expense';
    amount: number;
    description: string;
    transaction_date: string;
    reference_number?: string;
    payment_method?: string;
    category: Category;
    user?: {
        name: string;
    };
}

interface PaginatedData {
    data: Transaction[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

const props = defineProps<{
    transactions: PaginatedData;
    categories: Category[];
    summary: {
        total_income: number;
        total_expense: number;
        balance: number;
    };
    filters: {
        type?: string;
        category_id?: string;
        start_date?: string;
        end_date?: string;
    };
}>();

const breadcrumbs = [
    {
        title: 'การเงิน',
        href: '/finance',
    },
];

// Quick Transaction Modal
const showQuickModal = ref(false);

const openQuickModal = () => {
    showQuickModal.value = true;
};

const onTransactionSuccess = () => {
    router.reload({ only: ['transactions', 'summary'] });
};

// Keyboard shortcuts
const handleGlobalKeydown = (e: KeyboardEvent) => {
    // Ctrl+N or Cmd+N to open quick modal (when not in input)
    if ((e.ctrlKey || e.metaKey) && e.key === 'n' && !isInputFocused()) {
        e.preventDefault();
        openQuickModal();
    }
    // Alt+1 for quick income
    else if (e.altKey && e.key === '1' && !isInputFocused()) {
        e.preventDefault();
        openQuickModal();
    }
    // Alt+2 for quick expense
    else if (e.altKey && e.key === '2' && !isInputFocused()) {
        e.preventDefault();
        openQuickModal();
    }
};

const isInputFocused = () => {
    const activeElement = document.activeElement;
    return (
        activeElement?.tagName === 'INPUT' ||
        activeElement?.tagName === 'TEXTAREA' ||
        activeElement?.tagName === 'SELECT'
    );
};

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
});

// Filter form
// Default dates from backend (current month)
const filterForm = useForm({
    type: props.filters.type || '',
    category_id: props.filters.category_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const applyFilters = () => {
    filterForm.get('/finance', {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filterForm.reset();
    router.get('/finance');
};

// Delete confirmation
const deleteTransaction = (id: number) => {
    if (confirm('คุณต้องการลบรายการนี้หรือไม่?')) {
        router.delete(`/finance/${id}`);
    }
};

// Duplicate transaction
const duplicateTransaction = (transaction: Transaction) => {
    router.post('/finance', {
        type: transaction.type,
        category_id: transaction.category.id,
        amount: transaction.amount,
        description: transaction.description,
        transaction_date: new Date().toISOString().split('T')[0],
        payment_method: transaction.payment_method || 'cash',
        notes: '',
        reference_number: '',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['transactions', 'summary'] });
        },
    });
};

// Format currency
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('th-TH', {
        style: 'currency',
        currency: 'THB',
    }).format(amount);
};

// Format date
const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Payment method labels
const paymentMethodLabels: Record<string, string> = {
    cash: 'เงินสด',
    transfer: 'โอนเงิน',
    credit_card: 'บัตรเครดิต',
    cheque: 'เช็ค',
    other: 'อื่นๆ',
};

const getPaymentMethodLabel = (method?: string) => {
    return method ? paymentMethodLabels[method] || method : '-';
};
</script>

<template>
    <Head title="รายรับรายจ่าย" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 p-3 sm:p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-3 sm:gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight">รายรับรายจ่าย</h1>
                    <p class="text-sm text-muted-foreground">จัดการรายรับรายจ่ายของธุรกิจ</p>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                    <span class="hidden xl:inline text-xs text-muted-foreground">
                        กด <kbd class="px-1.5 py-0.5 bg-muted rounded text-xs font-mono">Ctrl+N</kbd> เพื่อเพิ่มด่วน
                    </span>
                    <Button as-child variant="outline" size="sm" class="hidden sm:inline-flex">
                        <Link href="/finance/categories">
                            <FolderOpen class="mr-1.5 h-4 w-4" />
                            หมวดหมู่
                        </Link>
                    </Button>
                    <Button as-child variant="outline" size="sm" class="hidden sm:inline-flex">
                        <Link href="/finance/report">
                            <BarChart3 class="mr-1.5 h-4 w-4" />
                            รายงาน
                        </Link>
                    </Button>
                    <Button @click="openQuickModal" size="sm" variant="secondary">
                        <Zap class="mr-1.5 h-4 w-4" />
                        บันทึกด่วน
                    </Button>
                    <Button as-child size="sm">
                        <Link href="/finance/create">
                            <Plus class="mr-1.5 h-4 w-4" />
                            <span class="hidden xs:inline">บันทึก</span>รายการ
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Quick Action Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-3">
                <button @click="openQuickModal" class="group">
                    <Card class="h-full transition-all hover:shadow-md hover:border-green-300 dark:hover:border-green-700 active:scale-[0.98]">
                        <CardContent class="p-3 sm:p-4">
                            <div class="flex items-center gap-2.5 mb-1">
                                <div class="rounded-lg bg-green-100 dark:bg-green-900/50 p-1.5">
                                    <TrendingUp class="h-4 w-4 text-green-600 dark:text-green-400" />
                                </div>
                            </div>
                            <p class="font-medium text-sm">เพิ่มรายรับ</p>
                            <p class="text-xs text-muted-foreground mt-0.5 opacity-0 group-hover:opacity-100 transition-opacity hidden sm:block">คลิกเพื่อเริ่ม</p>
                        </CardContent>
                    </Card>
                </button>
                <button @click="openQuickModal" class="group">
                    <Card class="h-full transition-all hover:shadow-md hover:border-red-300 dark:hover:border-red-700 active:scale-[0.98]">
                        <CardContent class="p-3 sm:p-4">
                            <div class="flex items-center gap-2.5 mb-1">
                                <div class="rounded-lg bg-red-100 dark:bg-red-900/50 p-1.5">
                                    <TrendingDown class="h-4 w-4 text-red-600 dark:text-red-400" />
                                </div>
                            </div>
                            <p class="font-medium text-sm">เพิ่มรายจ่าย</p>
                            <p class="text-xs text-muted-foreground mt-0.5 opacity-0 group-hover:opacity-100 transition-opacity hidden sm:block">คลิกเพื่อเริ่ม</p>
                        </CardContent>
                    </Card>
                </button>
                <Link href="/finance/report" class="group">
                    <Card class="h-full transition-all hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 active:scale-[0.98]">
                        <CardContent class="p-3 sm:p-4">
                            <div class="flex items-center gap-2.5 mb-1">
                                <div class="rounded-lg bg-purple-100 dark:bg-purple-900/50 p-1.5">
                                    <BarChart3 class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                                </div>
                            </div>
                            <p class="font-medium text-sm">ดูรายงาน</p>
                            <p class="text-xs text-muted-foreground mt-0.5 opacity-0 group-hover:opacity-100 transition-opacity hidden sm:block">วิเคราะห์ข้อมูล</p>
                        </CardContent>
                    </Card>
                </Link>
                <Link href="/finance/categories" class="group">
                    <Card class="h-full transition-all hover:shadow-md hover:border-primary/30 active:scale-[0.98]">
                        <CardContent class="p-3 sm:p-4">
                            <div class="flex items-center gap-2.5 mb-1">
                                <div class="rounded-lg bg-muted p-1.5">
                                    <FolderOpen class="h-4 w-4 text-muted-foreground" />
                                </div>
                            </div>
                            <p class="font-medium text-sm">จัดการหมวดหมู่</p>
                            <p class="text-xs text-muted-foreground mt-0.5 opacity-0 group-hover:opacity-100 transition-opacity hidden sm:block">เพิ่ม/แก้ไขหมวดหมู่</p>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-3 gap-2 sm:gap-4">
                <Card class="border-green-200 dark:border-green-800/50">
                    <CardContent class="p-3 sm:p-4">
                        <div class="flex items-center gap-2 mb-1">
                            <TrendingUp class="h-3.5 w-3.5 text-green-500" />
                            <span class="text-xs sm:text-sm font-medium text-green-600 dark:text-green-400">รายรับ</span>
                        </div>
                        <p class="text-base sm:text-2xl font-bold text-green-700 dark:text-green-300 truncate">{{ formatCurrency(summary.total_income) }}</p>
                    </CardContent>
                </Card>
                <Card class="border-red-200 dark:border-red-800/50">
                    <CardContent class="p-3 sm:p-4">
                        <div class="flex items-center gap-2 mb-1">
                            <TrendingDown class="h-3.5 w-3.5 text-red-500" />
                            <span class="text-xs sm:text-sm font-medium text-red-600 dark:text-red-400">รายจ่าย</span>
                        </div>
                        <p class="text-base sm:text-2xl font-bold text-red-700 dark:text-red-300 truncate">{{ formatCurrency(summary.total_expense) }}</p>
                    </CardContent>
                </Card>
                <Card :class="summary.balance >= 0 ? 'border-blue-200 dark:border-blue-800/50' : 'border-orange-200 dark:border-orange-800/50'">
                    <CardContent class="p-3 sm:p-4">
                        <div class="flex items-center gap-2 mb-1">
                            <Wallet :class="['h-3.5 w-3.5', summary.balance >= 0 ? 'text-blue-500' : 'text-orange-500']" />
                            <span :class="['text-xs sm:text-sm font-medium', summary.balance >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400']">คงเหลือ</span>
                        </div>
                        <p :class="['text-base sm:text-2xl font-bold truncate', summary.balance >= 0 ? 'text-blue-700 dark:text-blue-300' : 'text-orange-700 dark:text-orange-300']">{{ formatCurrency(summary.balance) }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardContent class="p-3 sm:p-4">
                    <form @submit.prevent="applyFilters" class="space-y-3 sm:space-y-0 sm:grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 sm:gap-3 lg:gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-medium mb-1">ประเภท</label>
                            <select v-model="filterForm.type" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                <option value="">ทั้งหมด</option>
                                <option value="income">รายรับ</option>
                                <option value="expense">รายจ่าย</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium mb-1">หมวดหมู่</label>
                            <select v-model="filterForm.category_id" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                <option value="">ทั้งหมด</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium mb-1">ตั้งแต่วันที่</label>
                            <input type="date" v-model="filterForm.start_date" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium mb-1">ถึงวันที่</label>
                            <input type="date" v-model="filterForm.end_date" class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                        </div>
                        <div class="flex items-end gap-2 pt-2 sm:pt-0">
                            <Button type="submit" size="sm" class="flex-1 sm:flex-none">
                                <Search class="mr-1.5 h-4 w-4" />
                                ค้นหา
                            </Button>
                            <Button type="button" @click="clearFilters" variant="outline" size="sm" class="flex-1 sm:flex-none">
                                <X class="mr-1.5 h-4 w-4" />
                                ล้าง
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- Transactions Table -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle class="flex items-center gap-2">
                        <FileText class="h-5 w-5" />
                        รายการทั้งหมด
                    </CardTitle>
                    <CardDescription>{{ transactions.total }} รายการ</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="text-left py-3 px-4 text-xs font-medium text-muted-foreground">วันที่</th>
                                    <th class="text-left py-3 px-4 text-xs font-medium text-muted-foreground">ประเภท</th>
                                    <th class="text-left py-3 px-4 text-xs font-medium text-muted-foreground">หมวดหมู่</th>
                                    <th class="text-left py-3 px-4 text-xs font-medium text-muted-foreground">รายละเอียด</th>
                                    <th class="text-left py-3 px-4 text-xs font-medium text-muted-foreground hidden lg:table-cell">วิธีชำระ</th>
                                    <th class="text-right py-3 px-4 text-xs font-medium text-muted-foreground">จำนวนเงิน</th>
                                    <th class="text-center py-3 px-4 text-xs font-medium text-muted-foreground">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="transaction in transactions.data" :key="transaction.id" class="border-b hover:bg-muted/50 transition-colors">
                                    <td class="py-3 px-4 whitespace-nowrap text-sm text-muted-foreground">
                                        {{ formatDate(transaction.transaction_date) }}
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <Badge :variant="transaction.type === 'income' ? 'outline' : 'destructive'" :class="transaction.type === 'income' ? 'border-green-300 bg-green-50 text-green-700 dark:border-green-700 dark:bg-green-900/30 dark:text-green-400' : ''">
                                            {{ transaction.type === 'income' ? 'รายรับ' : 'รายจ่าย' }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span 
                                            class="px-2 py-1 text-xs font-medium rounded-md"
                                            :style="{ backgroundColor: transaction.category?.color + '18', color: transaction.category?.color }"
                                        >
                                            {{ transaction.category?.name }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="text-sm">{{ transaction.description }}</div>
                                        <div v-if="transaction.reference_number" class="text-xs text-muted-foreground">
                                            อ้างอิง: {{ transaction.reference_number }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-sm text-muted-foreground hidden lg:table-cell">
                                        {{ getPaymentMethodLabel(transaction.payment_method) }}
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-right">
                                        <span :class="['font-semibold', transaction.type === 'income' ? 'text-green-600' : 'text-red-600']">
                                            {{ transaction.type === 'income' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <Button 
                                                @click="duplicateTransaction(transaction)" 
                                                variant="ghost" 
                                                size="icon" 
                                                class="h-8 w-8"
                                                title="คัดลอกรายการนี้"
                                            >
                                                <Copy class="h-3.5 w-3.5" />
                                            </Button>
                                            <Button as-child variant="ghost" size="icon" class="h-8 w-8">
                                                <Link :href="`/finance/${transaction.id}/edit`" title="แก้ไข">
                                                    <Pencil class="h-3.5 w-3.5" />
                                                </Link>
                                            </Button>
                                            <Button 
                                                @click="deleteTransaction(transaction.id)" 
                                                variant="ghost" 
                                                size="icon" 
                                                class="h-8 w-8 text-destructive hover:text-destructive"
                                                title="ลบ"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="transactions.data.length === 0">
                                    <td colspan="7" class="py-12 text-center">
                                        <Wallet class="h-12 w-12 mx-auto text-muted-foreground/50 mb-2" />
                                        <p class="text-muted-foreground">ไม่พบรายการ</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="transactions.last_page > 1" class="mt-4 flex justify-center border-t pt-4">
                        <nav class="flex gap-1">
                            <template v-for="link in transactions.links" :key="link.label">
                                <Button
                                    v-if="link.url"
                                    as-child
                                    :variant="link.active ? 'default' : 'outline'"
                                    size="sm"
                                    class="h-8 min-w-8"
                                >
                                    <Link :href="link.url">
                                        <!-- eslint-disable-next-line vue/no-v-html -->
                                        <span v-html="link.label" />
                                    </Link>
                                </Button>
                                <Button
                                    v-else
                                    variant="ghost"
                                    size="sm"
                                    class="h-8 min-w-8"
                                    disabled
                                >
                                    <!-- eslint-disable-next-line vue/no-v-html -->
                                    <span v-html="link.label" />
                                </Button>
                            </template>
                        </nav>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Quick Transaction Modal -->
        <QuickTransactionModal 
            v-model="showQuickModal" 
            :categories="categories"
            @success="onTransactionSuccess"
        />
    </AppLayout>
</template>
