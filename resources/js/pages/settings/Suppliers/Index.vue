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

interface Supplier {
    id: number;
    name: string;
    contact_person?: string;
    phone?: string;
    email?: string;
    address?: string;
}

defineProps<{
    suppliers: Supplier[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'การตั้งค่า',
        href: '/settings',
    },
    {
        title: 'ซัพพลายเออร์',
        href: '/settings/suppliers',
    },
];

const showModal = ref(false);
const editingSupplier = ref<Supplier | null>(null);

const form = useForm({
    name: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
});

const deleteForm = useForm({});

const openCreateModal = () => {
    editingSupplier.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (supplier: Supplier) => {
    editingSupplier.value = supplier;
    form.name = supplier.name;
    form.contact_person = supplier.contact_person || '';
    form.phone = supplier.phone || '';
    form.email = supplier.email || '';
    form.address = supplier.address || '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingSupplier.value = null;
    form.reset();
};

const submitForm = () => {
    if (editingSupplier.value) {
        form.put(`/settings/suppliers/${editingSupplier.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/settings/suppliers', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteSupplier = (supplier: Supplier) => {
    if (confirm('คุณต้องการลบซัพพลายเออร์นี้หรือไม่?')) {
        deleteForm.delete(`/settings/suppliers/${supplier.id}`);
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Suppliers" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-col space-y-6">
                    <div class="flex justify-between items-center">
                    <HeadingSmall
                        title="ซัพพลายเออร์"
                        description="จัดการซัพพลายเออร์ของคุณ"
                    />
                    <Button @click="openCreateModal">
                        เพิ่มซัพพลายเออร์
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
                                    ผู้ติดต่อ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    เบอร์โทร
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    อีเมล
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    จัดการ
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="supplier in suppliers" :key="supplier.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ supplier.name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ supplier.contact_person || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ supplier.phone || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ supplier.email || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button
                                        @click="openEditModal(supplier)"
                                        class="text-indigo-600 hover:text-indigo-900 mr-4"
                                    >
                                        แก้ไข
                                    </button>
                                    <button
                                        @click="deleteSupplier(supplier)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        ลบ
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="suppliers.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    ไม่พบซัพพลายเออร์ คลิก "เพิ่มซัพพลายเออร์" เพื่อสร้าง
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
                                    {{ editingSupplier ? 'แก้ไขซัพพลายเออร์' : 'เพิ่มซัพพลายเออร์' }}
                                </h3>

                                <div class="space-y-4">
                                    <div>
                                        <Label for="name">ชื่อ *</Label>
                                        <Input
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            required
                                        />
                                        <InputError :message="form.errors.name" />
                                    </div>

                                    <div>
                                        <Label for="contact_person">ผู้ติดต่อ</Label>
                                        <Input
                                            id="contact_person"
                                            v-model="form.contact_person"
                                            type="text"
                                        />
                                        <InputError :message="form.errors.contact_person" />
                                    </div>

                                    <div>
                                        <Label for="phone">เบอร์โทร</Label>
                                        <Input
                                            id="phone"
                                            v-model="form.phone"
                                            type="text"
                                        />
                                        <InputError :message="form.errors.phone" />
                                    </div>

                                    <div>
                                        <Label for="email">อีเมล</Label>
                                        <Input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                        />
                                        <InputError :message="form.errors.email" />
                                    </div>

                                    <div>
                                        <Label for="address">ที่อยู่</Label>
                                        <textarea
                                            id="address"
                                            v-model="form.address"
                                            rows="3"
                                            class="w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                                        />
                                        <InputError :message="form.errors.address" />
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <Button
                                    type="submit"
                                    class="w-full sm:ml-3 sm:w-auto"
                                    :disabled="form.processing"
                                >
                                    {{ editingSupplier ? 'บันทึก' : 'สร้าง' }}
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
