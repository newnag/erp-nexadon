<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Category {
    id: number;
    name: string;
    type: 'income' | 'expense';
    color: string;
}

const props = defineProps<{
    categories: Category[];
}>();

const breadcrumbs = [
    {
        title: 'การเงิน',
        href: '/finance',
    },
    {
        title: 'บันทึกรายการ',
        href: '/finance/create',
    },
];

const form = useForm({
    type: 'expense' as 'income' | 'expense',
    category_id: '',
    amount: '',
    description: '',
    notes: '',
    transaction_date: new Date().toISOString().split('T')[0],
    reference_number: '',
    payment_method: 'cash',
});

const filteredCategories = computed(() => {
    return props.categories.filter(cat => cat.type === form.type);
});

const submit = () => {
    form.post('/finance', {
        onSuccess: () => {
            form.reset();
        },
    });
};

// Watch for type change to reset category
const onTypeChange = () => {
    form.category_id = '';
};
</script>

<template>
    <Head title="บันทึกรายรับรายจ่าย" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6 px-6">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        บันทึกรายรับรายจ่าย
                    </h2>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <!-- Transaction Type -->
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-bold mb-2">ประเภทรายการ</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center">
                                        <input 
                                            type="radio" 
                                            v-model="form.type" 
                                            value="income" 
                                            @change="onTypeChange"
                                            class="form-radio text-green-500"
                                        >
                                        <span class="ml-2 text-green-600 font-medium">รายรับ</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input 
                                            type="radio" 
                                            v-model="form.type" 
                                            value="expense" 
                                            @change="onTypeChange"
                                            class="form-radio text-red-500"
                                        >
                                        <span class="ml-2 text-red-600 font-medium">รายจ่าย</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">หมวดหมู่</label>
                                <select 
                                    v-model="form.category_id" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    required
                                >
                                    <option value="">เลือกหมวดหมู่</option>
                                    <option v-for="category in filteredCategories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.category_id" class="text-red-500 text-xs italic mt-1">
                                    {{ form.errors.category_id }}
                                </div>
                                <p v-if="filteredCategories.length === 0" class="text-orange-500 text-xs mt-1">
                                    ไม่มีหมวดหมู่สำหรับประเภทนี้ กรุณา <Link href="/finance/categories" class="underline">สร้างหมวดหมู่</Link> ก่อน
                                </p>
                            </div>

                            <!-- Amount -->
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">จำนวนเงิน (บาท)</label>
                                <input 
                                    v-model="form.amount" 
                                    type="number" 
                                    step="0.01" 
                                    min="0.01"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                    required
                                    placeholder="0.00"
                                >
                                <div v-if="form.errors.amount" class="text-red-500 text-xs italic mt-1">
                                    {{ form.errors.amount }}
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">รายละเอียด</label>
                                <input 
                                    v-model="form.description" 
                                    type="text" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                    required
                                    placeholder="เช่น ค่าอาหาร, รายได้จากการขาย"
                                >
                                <div v-if="form.errors.description" class="text-red-500 text-xs italic mt-1">
                                    {{ form.errors.description }}
                                </div>
                            </div>

                            <!-- Transaction Date -->
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">วันที่</label>
                                <input 
                                    v-model="form.transaction_date" 
                                    type="date" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                    required
                                >
                                <div v-if="form.errors.transaction_date" class="text-red-500 text-xs italic mt-1">
                                    {{ form.errors.transaction_date }}
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">วิธีชำระเงิน</label>
                                <select 
                                    v-model="form.payment_method" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                >
                                    <option value="cash">เงินสด</option>
                                    <option value="transfer">โอนเงิน</option>
                                    <option value="credit_card">บัตรเครดิต</option>
                                    <option value="cheque">เช็ค</option>
                                    <option value="other">อื่นๆ</option>
                                </select>
                            </div>

                            <!-- Reference Number -->
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">เลขที่อ้างอิง (ไม่บังคับ)</label>
                                <input 
                                    v-model="form.reference_number" 
                                    type="text" 
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    placeholder="เช่น เลขที่ใบเสร็จ"
                                >
                            </div>

                            <!-- Notes -->
                            <div class="mb-6">
                                <label class="block text-gray-700 text-sm font-bold mb-2">หมายเหตุ (ไม่บังคับ)</label>
                                <textarea 
                                    v-model="form.notes" 
                                    rows="3"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                                    placeholder="รายละเอียดเพิ่มเติม"
                                ></textarea>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center justify-between">
                                <Link href="/finance" class="text-gray-600 hover:text-gray-900">
                                    ← กลับ
                                </Link>
                                <button 
                                    type="submit" 
                                    :class="[
                                        'font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline',
                                        form.type === 'income' 
                                            ? 'bg-green-500 hover:bg-green-700 text-white' 
                                            : 'bg-red-500 hover:bg-red-700 text-white'
                                    ]"
                                    :disabled="form.processing"
                                >
                                    {{ form.processing ? 'กำลังบันทึก...' : 'บันทึกรายการ' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
