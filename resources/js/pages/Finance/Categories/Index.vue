<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
    type: 'income' | 'expense';
    description?: string;
    color: string;
    is_active: boolean;
    transactions_count: number;
}

defineProps<{
    categories: Category[];
}>();

const breadcrumbs = [
    {
        title: 'การเงิน',
        href: '/finance',
    },
    {
        title: 'หมวดหมู่',
        href: '/finance/categories',
    },
];

// Create form
const createForm = useForm({
    name: '',
    type: 'expense' as 'income' | 'expense',
    description: '',
    color: '#3b82f6',
});

// Edit form
const editingCategory = ref<Category | null>(null);
const editForm = useForm({
    name: '',
    type: 'expense' as 'income' | 'expense',
    description: '',
    color: '#3b82f6',
    is_active: true,
});

const showCreateModal = ref(false);

const openCreateModal = () => {
    createForm.reset();
    showCreateModal.value = true;
};

const submitCreate = () => {
    createForm.post('/finance/categories', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

const openEditModal = (category: Category) => {
    editingCategory.value = category;
    editForm.name = category.name;
    editForm.type = category.type;
    editForm.description = category.description || '';
    editForm.color = category.color;
    editForm.is_active = category.is_active;
};

const submitEdit = () => {
    if (!editingCategory.value) return;
    
    editForm.put(`/finance/categories/${editingCategory.value.id}`, {
        onSuccess: () => {
            editingCategory.value = null;
        },
    });
};

const deleteCategory = (category: Category) => {
    if (category.transactions_count > 0) {
        alert('ไม่สามารถลบหมวดหมู่ที่มีรายการธุรกรรมได้');
        return;
    }
    
    if (confirm(`คุณต้องการลบหมวดหมู่ "${category.name}" หรือไม่?`)) {
        router.delete(`/finance/categories/${category.id}`);
    }
};

// Color presets
const colorPresets = [
    '#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16',
    '#22c55e', '#10b981', '#14b8a6', '#06b6d4', '#0ea5e9',
    '#3b82f6', '#6366f1', '#8b5cf6', '#a855f7', '#d946ef',
    '#ec4899', '#f43f5e', '#64748b', '#78716c', '#71717a',
];
</script>

<template>
    <Head title="หมวดหมู่การเงิน" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        หมวดหมู่การเงิน
                    </h2>
                    <div class="flex gap-2">
                        <Link href="/finance" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            ← กลับ
                        </Link>
                        <button @click="openCreateModal" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            เพิ่มหมวดหมู่
                        </button>
                    </div>
                </div>

                <!-- Income Categories -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-green-600 mb-4">หมวดหมู่รายรับ</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div 
                                v-for="category in categories.filter(c => c.type === 'income')" 
                                :key="category.id"
                                class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                                :class="{ 'opacity-50': !category.is_active }"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-2">
                                        <div 
                                            class="w-4 h-4 rounded-full" 
                                            :style="{ backgroundColor: category.color }"
                                        ></div>
                                        <span class="font-medium">{{ category.name }}</span>
                                    </div>
                                    <div class="flex gap-1">
                                        <button @click="openEditModal(category)" class="text-blue-600 hover:text-blue-900 text-sm">
                                            แก้ไข
                                        </button>
                                        <button 
                                            @click="deleteCategory(category)" 
                                            class="text-red-600 hover:text-red-900 text-sm"
                                            :disabled="category.transactions_count > 0"
                                            :class="{ 'opacity-50 cursor-not-allowed': category.transactions_count > 0 }"
                                        >
                                            ลบ
                                        </button>
                                    </div>
                                </div>
                                <p v-if="category.description" class="text-sm text-gray-500 mt-1">
                                    {{ category.description }}
                                </p>
                                <div class="text-xs text-gray-400 mt-2">
                                    {{ category.transactions_count }} รายการ
                                    <span v-if="!category.is_active" class="text-orange-500 ml-2">(ปิดใช้งาน)</span>
                                </div>
                            </div>
                            <div 
                                v-if="categories.filter(c => c.type === 'income').length === 0"
                                class="col-span-full text-center text-gray-500 py-4"
                            >
                                ยังไม่มีหมวดหมู่รายรับ
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expense Categories -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-red-600 mb-4">หมวดหมู่รายจ่าย</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div 
                                v-for="category in categories.filter(c => c.type === 'expense')" 
                                :key="category.id"
                                class="border rounded-lg p-4 hover:shadow-md transition-shadow"
                                :class="{ 'opacity-50': !category.is_active }"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-2">
                                        <div 
                                            class="w-4 h-4 rounded-full" 
                                            :style="{ backgroundColor: category.color }"
                                        ></div>
                                        <span class="font-medium">{{ category.name }}</span>
                                    </div>
                                    <div class="flex gap-1">
                                        <button @click="openEditModal(category)" class="text-blue-600 hover:text-blue-900 text-sm">
                                            แก้ไข
                                        </button>
                                        <button 
                                            @click="deleteCategory(category)" 
                                            class="text-red-600 hover:text-red-900 text-sm"
                                            :disabled="category.transactions_count > 0"
                                            :class="{ 'opacity-50 cursor-not-allowed': category.transactions_count > 0 }"
                                        >
                                            ลบ
                                        </button>
                                    </div>
                                </div>
                                <p v-if="category.description" class="text-sm text-gray-500 mt-1">
                                    {{ category.description }}
                                </p>
                                <div class="text-xs text-gray-400 mt-2">
                                    {{ category.transactions_count }} รายการ
                                    <span v-if="!category.is_active" class="text-orange-500 ml-2">(ปิดใช้งาน)</span>
                                </div>
                            </div>
                            <div 
                                v-if="categories.filter(c => c.type === 'expense').length === 0"
                                class="col-span-full text-center text-gray-500 py-4"
                            >
                                ยังไม่มีหมวดหมู่รายจ่าย
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl w-96">
                <h3 class="text-lg font-bold mb-4">เพิ่มหมวดหมู่</h3>
                <form @submit.prevent="submitCreate">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อหมวดหมู่</label>
                        <input 
                            v-model="createForm.name" 
                            type="text" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            required
                        >
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">ประเภท</label>
                        <select 
                            v-model="createForm.type" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        >
                            <option value="income">รายรับ</option>
                            <option value="expense">รายจ่าย</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">คำอธิบาย</label>
                        <input 
                            v-model="createForm.description" 
                            type="text" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        >
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">สี</label>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <button
                                v-for="color in colorPresets"
                                :key="color"
                                type="button"
                                class="w-6 h-6 rounded-full border-2"
                                :style="{ backgroundColor: color }"
                                :class="{ 'border-gray-800': createForm.color === color, 'border-transparent': createForm.color !== color }"
                                @click="createForm.color = color"
                            ></button>
                        </div>
                        <input 
                            v-model="createForm.color" 
                            type="color" 
                            class="w-full h-10 rounded cursor-pointer"
                        >
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="showCreateModal = false" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            ยกเลิก
                        </button>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" :disabled="createForm.processing">
                            สร้าง
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="editingCategory" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl w-96">
                <h3 class="text-lg font-bold mb-4">แก้ไขหมวดหมู่</h3>
                <form @submit.prevent="submitEdit">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อหมวดหมู่</label>
                        <input 
                            v-model="editForm.name" 
                            type="text" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                            required
                        >
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">ประเภท</label>
                        <select 
                            v-model="editForm.type" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                            :disabled="editingCategory.transactions_count > 0"
                        >
                            <option value="income">รายรับ</option>
                            <option value="expense">รายจ่าย</option>
                        </select>
                        <p v-if="editingCategory.transactions_count > 0" class="text-xs text-orange-500 mt-1">
                            ไม่สามารถเปลี่ยนประเภทได้เนื่องจากมีรายการธุรกรรมอยู่
                        </p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">คำอธิบาย</label>
                        <input 
                            v-model="editForm.description" 
                            type="text" 
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                        >
                    </div>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">สี</label>
                        <div class="flex flex-wrap gap-2 mb-2">
                            <button
                                v-for="color in colorPresets"
                                :key="color"
                                type="button"
                                class="w-6 h-6 rounded-full border-2"
                                :style="{ backgroundColor: color }"
                                :class="{ 'border-gray-800': editForm.color === color, 'border-transparent': editForm.color !== color }"
                                @click="editForm.color = color"
                            ></button>
                        </div>
                        <input 
                            v-model="editForm.color" 
                            type="color" 
                            class="w-full h-10 rounded cursor-pointer"
                        >
                    </div>
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                v-model="editForm.is_active"
                                class="form-checkbox"
                            >
                            <span class="ml-2">เปิดใช้งาน</span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="editingCategory = null" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                            ยกเลิก
                        </button>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" :disabled="editForm.processing">
                            บันทึก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
