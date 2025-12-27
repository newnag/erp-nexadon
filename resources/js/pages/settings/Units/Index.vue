<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

interface Unit {
    id: number;
    name: string;
    abbreviation: string;
    description?: string;
}

defineProps<{
    units: Unit[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'การตั้งค่า',
        href: '/settings',
    },
    {
        title: 'หน่วยวัด',
        href: '/settings/units',
    },
];

const showModal = ref(false);
const editingUnit = ref<Unit | null>(null);

const form = useForm({
    name: '',
    abbreviation: '',
    description: '',
});

const deleteForm = useForm({});

const openCreateModal = () => {
    editingUnit.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (unit: Unit) => {
    editingUnit.value = unit;
    form.name = unit.name;
    form.abbreviation = unit.abbreviation;
    form.description = unit.description || '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingUnit.value = null;
    form.reset();
};

const submitForm = () => {
    if (editingUnit.value) {
        form.put(`/settings/units/${editingUnit.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/settings/units', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteUnit = (unit: Unit) => {
    if (confirm('คุณต้องการลบหน่วยวัดนี้หรือไม่?')) {
        deleteForm.delete(`/settings/units/${unit.id}`);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Units" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-col space-y-6">
                    <div class="flex justify-between items-center">
                    <HeadingSmall
                        title="หน่วยวัด"
                        description="จัดการหน่วยวัดสำหรับวัตถุดิบของคุณ"
                    />
                    <Button @click="openCreateModal">
                        เพิ่มหน่วยวัด
                    </Button>
                </div>

                <div class="bg-white shadow-sm rounded-lg border">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ชื่อ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ตัวย่อ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    คำอธิบาย
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    จัดการ
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="unit in units" :key="unit.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ unit.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ unit.abbreviation }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">
                                        {{ unit.description || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button
                                        @click="openEditModal(unit)"
                                        class="text-indigo-600 hover:text-indigo-900 mr-4"
                                    >
                                        แก้ไข
                                    </button>
                                    <button
                                        @click="deleteUnit(unit)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        ลบ
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="units.length === 0">
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    ไม่พบหน่วยวัด คลิก "เพิ่มหน่วยวัด" เพื่อสร้าง
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Modal -->
                <div
                    v-if="showModal"
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity z-50"
                    @click="closeModal"
                >
                    <div class="flex items-center justify-center min-h-screen px-4">
                    <div
                        class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full"
                        @click.stop
                    >
                        <form @submit.prevent="submitForm">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                                    {{ editingUnit ? 'แก้ไขหน่วยวัด' : 'เพิ่มหน่วยวัด' }}
                                </h3>

                                <div class="space-y-4">
                                    <div>
                                        <Label for="name">ชื่อ *</Label>
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            placeholder="เช่น กิโลกรัม, ลิตร, ชิ้น"
                                            required
                                        />
                                        <InputError :message="form.errors.name" />
                                    </div>

                                    <div>
                                        <Label for="abbreviation">ตัวย่อ *</Label>
                                        <Input
                                            id="abbreviation"
                                            v-model="form.abbreviation"
                                            type="text"
                                            placeholder="เช่น kg, L, pcs"
                                            required
                                        />
                                        <InputError :message="form.errors.abbreviation" />
                                    </div>

                                    <div>
                                        <Label for="description">คำอธิบาย</Label>
                                        <textarea
                                            id="description"
                                            v-model="form.description"
                                            rows="3"
                                            placeholder="คำอธิบายหน่วยวัด (ไม่บังคับ)"
                                            class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                        />
                                        <InputError :message="form.errors.description" />
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <Button
                                    type="submit"
                                    class="w-full sm:ml-3 sm:w-auto"
                                    :disabled="form.processing"
                                >
                                    {{ editingUnit ? 'บันทึก' : 'สร้าง' }}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    class="mt-3 w-full sm:mt-0 sm:w-auto"
                                    @click="closeModal"
                                >
                                    ยกเลิก
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
                </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>