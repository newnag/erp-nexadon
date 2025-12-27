<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { store } from '@/routes/ingredients';
import { Head, useForm } from '@inertiajs/vue3';

const breadcrumbs = [
    {
        title: 'วัตถุดิบ',
        href: '/ingredients',
    },
    {
        title: 'สร้าง',
        href: '/ingredients/create',
    },
];

defineProps<{
    suppliers: Array<{
        id: number;
        name: string;
    }>;
    units: Array<{
        id: number;
        name: string;
        abbreviation: string;
    }>;
}>();

const form = useForm({
    name: '',
    supplier_id: '',
    purchase_unit: '',
    cost_per_unit: '',
    reorder_point: 0,
});

const submit = () => {
    form.post(store().url);
};
</script>

<template>
    <Head title="Create Ingredient" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6 px-6">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        สร้างวัตถุดิบ
                    </h2>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อ</label>
                                <input v-model="form.name" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                <div v-if="form.errors.name" class="text-red-500 text-xs italic">{{ form.errors.name }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">ซัพพลายเออร์</label>
                                <select v-model="form.supplier_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="">เลือกซัพพลายเออร์</option>
                                    <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.name }}</option>
                                </select>
                                <div v-if="form.errors.supplier_id" class="text-red-500 text-xs italic">{{ form.errors.supplier_id }}</div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">หน่วยซื้อ</label>
                                    <select v-model="form.purchase_unit" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                        <option value="">เลือกหน่วย</option>
                                        <option v-for="unit in units" :key="unit.id" :value="unit.abbreviation">
                                            {{ unit.name }} ({{ unit.abbreviation }})
                                        </option>
                                    </select>
                                    <div v-if="form.errors.purchase_unit" class="text-red-500 text-xs italic">{{ form.errors.purchase_unit }}</div>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">ราคาต่อหน่วย (บาท)</label>
                                    <input v-model="form.cost_per_unit" type="number" step="0.01" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    <div v-if="form.errors.cost_per_unit" class="text-red-500 text-xs italic">{{ form.errors.cost_per_unit }}</div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">จุดสั่งซื้อซ้ำ</label>
                                <input v-model="form.reorder_point" type="number" step="0.0001" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>

                            <div class="flex items-center justify-between">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" :disabled="form.processing">
                                    สร้างวัตถุดิบ
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
