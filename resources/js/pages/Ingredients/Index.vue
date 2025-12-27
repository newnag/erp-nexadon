<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create } from '@/routes/ingredients';
import { Head, Link } from '@inertiajs/vue3';

const breadcrumbs = [
    {
        title: 'วัตถุดิบ',
        href: '/ingredients',
    },
];

defineProps<{
    ingredients: Array<{
        id: number;
        name: string;
        purchase_unit: string;
        cost_per_unit: number;
        current_stock: number;
        reorder_point?: number;
        supplier?: {
            name: string;
        };
    }>;
}>();

const formatNumber = (num: number) => {
    return new Intl.NumberFormat('th-TH').format(num);
};

const formatCurrency = (num: number) => {
    return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(num);
};

const isLowStock = (ingredient: { current_stock: number; reorder_point?: number }) => {
    if (!ingredient.reorder_point) return false;
    return ingredient.current_stock <= ingredient.reorder_point;
};
</script>

<template>
    <Head title="วัตถุดิบ" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold">วัตถุดิบ</h1>
                    <p class="text-muted-foreground">รายการวัตถุดิบทั้งหมดในระบบ</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/stock" class="inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                        จัดการสต็อค
                    </Link>
                    <Link :href="create().url" class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                        เพิ่มวัตถุดิบ
                    </Link>
                </div>
            </div>

            <div class="rounded-lg border bg-card">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="text-left py-3 px-4 font-medium">ชื่อ</th>
                                <th class="text-left py-3 px-4 font-medium">ซัพพลายเออร์</th>
                                <th class="text-left py-3 px-4 font-medium">หน่วย</th>
                                <th class="text-right py-3 px-4 font-medium">ราคา/หน่วย</th>
                                <th class="text-right py-3 px-4 font-medium">สต็อคปัจจุบัน</th>
                                <th class="text-right py-3 px-4 font-medium">จุดสั่งซื้อ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ingredient in ingredients" :key="ingredient.id" class="border-b hover:bg-muted/50">
                                <td class="py-3 px-4">
                                    <span class="font-medium">{{ ingredient.name }}</span>
                                </td>
                                <td class="py-3 px-4 text-muted-foreground">{{ ingredient.supplier?.name || '-' }}</td>
                                <td class="py-3 px-4">{{ ingredient.purchase_unit }}</td>
                                <td class="py-3 px-4 text-right">{{ formatCurrency(ingredient.cost_per_unit) }}</td>
                                <td class="py-3 px-4 text-right">
                                    <span :class="{ 'text-red-600 font-semibold': isLowStock(ingredient) }">
                                        {{ formatNumber(ingredient.current_stock) }}
                                    </span>
                                    <span v-if="isLowStock(ingredient)" class="ml-1 text-xs text-red-500">(ต่ำ)</span>
                                </td>
                                <td class="py-3 px-4 text-right text-muted-foreground">
                                    {{ ingredient.reorder_point ? formatNumber(ingredient.reorder_point) : '-' }}
                                </td>
                            </tr>
                            <tr v-if="ingredients.length === 0">
                                <td colspan="6" class="py-8 text-center text-muted-foreground">
                                    ยังไม่มีวัตถุดิบ
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
