<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import QuickTransactionModal from '@/components/QuickTransactionModal.vue';
import { Button } from '@/components/ui/button';

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
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        รายรับรายจ่าย
                    </h2>
                    <div class="flex gap-2 items-center">
                        <!-- Keyboard shortcut hint -->
                        <span class="hidden lg:inline text-xs text-gray-400">
                            กด <kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-gray-600">Ctrl+N</kbd> เพื่อเพิ่มรายการด่วน
                        </span>
                        <Link href="/finance/categories" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            จัดการหมวดหมู่
                        </Link>
                        <Link href="/finance/report" class="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                            ดูรายงาน
                        </Link>
                        <Button @click="openQuickModal" class="bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white font-bold py-2 px-4 rounded shadow-lg">
                            ⚡ บันทึกด่วน
                        </Button>
                        <Link href="/finance/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            บันทึกรายการ
                        </Link>
                    </div>
                </div>

                <!-- Quick Action Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <button
                        @click="openQuickModal"
                        class="p-4 bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg hover:shadow-md transition-all text-left group"
                    >
                        <div class="text-2xl mb-1">📈</div>
                        <div class="font-medium text-green-700">เพิ่มรายรับ</div>
                        <div class="text-xs text-green-600 opacity-0 group-hover:opacity-100 transition-opacity">คลิกเพื่อเริ่ม</div>
                    </button>
                    <button
                        @click="openQuickModal"
                        class="p-4 bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-lg hover:shadow-md transition-all text-left group"
                    >
                        <div class="text-2xl mb-1">📉</div>
                        <div class="font-medium text-red-700">เพิ่มรายจ่าย</div>
                        <div class="text-xs text-red-600 opacity-0 group-hover:opacity-100 transition-opacity">คลิกเพื่อเริ่ม</div>
                    </button>
                    <Link
                        href="/finance/report"
                        class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200 rounded-lg hover:shadow-md transition-all text-left group"
                    >
                        <div class="text-2xl mb-1">📊</div>
                        <div class="font-medium text-purple-700">ดูรายงาน</div>
                        <div class="text-xs text-purple-600 opacity-0 group-hover:opacity-100 transition-opacity">วิเคราะห์ข้อมูล</div>
                    </Link>
                    <Link
                        href="/finance/categories"
                        class="p-4 bg-gradient-to-br from-gray-50 to-gray-100 border border-gray-200 rounded-lg hover:shadow-md transition-all text-left group"
                    >
                        <div class="text-2xl mb-1">📁</div>
                        <div class="font-medium text-gray-700">จัดการหมวดหมู่</div>
                        <div class="text-xs text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity">เพิ่ม/แก้ไขหมวดหมู่</div>
                    </Link>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                        <h3 class="text-sm font-medium text-green-600">รายรับ</h3>
                        <p class="text-2xl font-bold text-green-700">{{ formatCurrency(summary.total_income) }}</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                        <h3 class="text-sm font-medium text-red-600">รายจ่าย</h3>
                        <p class="text-2xl font-bold text-red-700">{{ formatCurrency(summary.total_expense) }}</p>
                    </div>
                    <div :class="[
                        'rounded-lg p-4 border',
                        summary.balance >= 0 
                            ? 'bg-blue-50 border-blue-200' 
                            : 'bg-orange-50 border-orange-200'
                    ]">
                        <h3 :class="[
                            'text-sm font-medium',
                            summary.balance >= 0 ? 'text-blue-600' : 'text-orange-600'
                        ]">คงเหลือ</h3>
                        <p :class="[
                            'text-2xl font-bold',
                            summary.balance >= 0 ? 'text-blue-700' : 'text-orange-700'
                        ]">{{ formatCurrency(summary.balance) }}</p>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
                    <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ประเภท</label>
                            <select v-model="filterForm.type" class="w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">ทั้งหมด</option>
                                <option value="income">รายรับ</option>
                                <option value="expense">รายจ่าย</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">หมวดหมู่</label>
                            <select v-model="filterForm.category_id" class="w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">ทั้งหมด</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ตั้งแต่วันที่</label>
                            <input type="date" v-model="filterForm.start_date" class="w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ถึงวันที่</label>
                            <input type="date" v-model="filterForm.end_date" class="w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                ค้นหา
                            </button>
                            <button type="button" @click="clearFilters" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                ล้าง
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Transactions Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-4 sm:p-6 bg-white border-b border-gray-200">
                        <div class="overflow-x-auto -mx-4 sm:mx-0">
                            <div class="inline-block min-w-full align-middle px-4 sm:px-0">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วันที่</th>
                                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ประเภท</th>
                                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">หมวดหมู่</th>
                                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รายละเอียด</th>
                                            <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">วิธีชำระ</th>
                                            <th class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จำนวนเงิน</th>
                                            <th class="px-4 sm:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="transaction in transactions.data" :key="transaction.id">
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ formatDate(transaction.transaction_date) }}
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                                <span :class="[
                                                    'px-2 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                    transaction.type === 'income' 
                                                        ? 'bg-green-100 text-green-800' 
                                                        : 'bg-red-100 text-red-800'
                                                ]">
                                                    {{ transaction.type === 'income' ? 'รายรับ' : 'รายจ่าย' }}
                                                </span>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                                <span 
                                                    class="px-2 py-1 text-xs font-medium rounded"
                                                    :style="{ backgroundColor: transaction.category?.color + '20', color: transaction.category?.color }"
                                                >
                                                    {{ transaction.category?.name }}
                                                </span>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4">
                                                <div class="text-sm text-gray-900">{{ transaction.description }}</div>
                                                <div v-if="transaction.reference_number" class="text-xs text-gray-500">
                                                    อ้างอิง: {{ transaction.reference_number }}
                                                </div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ getPaymentMethodLabel(transaction.payment_method) }}
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right">
                                                <span :class="[
                                                    'font-semibold',
                                                    transaction.type === 'income' ? 'text-green-600' : 'text-red-600'
                                                ]">
                                                    {{ transaction.type === 'income' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                                                </span>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <button 
                                                    @click="duplicateTransaction(transaction)" 
                                                    class="text-green-600 hover:text-green-900 mr-2"
                                                    title="คัดลอกรายการนี้"
                                                >
                                                    📋
                                                </button>
                                                <Link :href="`/finance/${transaction.id}/edit`" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                    แก้ไข
                                                </Link>
                                                <button @click="deleteTransaction(transaction.id)" class="text-red-600 hover:text-red-900">
                                                    ลบ
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="transactions.data.length === 0">
                                            <td colspan="7" class="px-4 sm:px-6 py-4 text-center text-gray-500">
                                                ไม่พบรายการ
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="transactions.last_page > 1" class="mt-4 flex justify-center">
                            <nav class="flex gap-1">
                                <template v-for="link in transactions.links" :key="link.label">
                                    <Link
                                        v-if="link.url"
                                        :href="link.url"
                                        :class="[
                                            'px-3 py-2 rounded text-sm',
                                            link.active 
                                                ? 'bg-blue-500 text-white' 
                                                : 'bg-gray-100 hover:bg-gray-200 text-gray-700'
                                        ]"
                                    >
                                        <!-- eslint-disable-next-line vue/no-v-html -->
                                        <span v-html="link.label" />
                                    </Link>
                                    <span
                                        v-else
                                        class="px-3 py-2 text-sm text-gray-400"
                                    >
                                        <!-- eslint-disable-next-line vue/no-v-html -->
                                        <span v-html="link.label" />
                                    </span>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Transaction Modal -->
        <QuickTransactionModal 
            v-model="showQuickModal" 
            :categories="categories"
            @success="onTransactionSuccess"
        />
    </AppLayout>
</template>
