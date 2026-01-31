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
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 p-4 sm:p-6">
            <!-- Header - Responsive -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold">วัตถุดิบ</h1>
                    <p class="text-sm text-muted-foreground">รายการวัตถุดิบทั้งหมดในระบบ</p>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <Link href="/stock" class="flex-1 sm:flex-none inline-flex items-center justify-center rounded-md text-sm font-medium border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 sm:h-10 px-3 sm:px-4 py-2 transition-colors">
                        <span class="hidden xs:inline">จัดการ</span>สต็อค
                    </Link>
                    <Link :href="create().url" class="flex-1 sm:flex-none inline-flex items-center justify-center rounded-md text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 h-9 sm:h-10 px-3 sm:px-4 py-2 transition-colors">
                        + เพิ่มวัตถุดิบ
                    </Link>
                </div>
            </div>

            <!-- Mobile Card View -->
            <div class="block md:hidden space-y-3">
                <div v-for="ingredient in ingredients" :key="ingredient.id" 
                    class="rounded-lg border bg-card p-4 hover:bg-muted/50 transition-colors"
                >
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="font-semibold text-base">{{ ingredient.name }}</h3>
                            <p class="text-sm text-muted-foreground">{{ ingredient.supplier?.name || 'ไม่ระบุซัพพลายเออร์' }}</p>
                        </div>
                        <span 
                            v-if="isLowStock(ingredient)" 
                            class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400"
                        >
                            สต็อคต่ำ
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <span class="text-muted-foreground">หน่วย:</span>
                            <span class="ml-1 font-medium">{{ ingredient.purchase_unit }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">ราคา:</span>
                            <span class="ml-1 font-medium">{{ formatCurrency(ingredient.cost_per_unit) }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">สต็อค:</span>
                            <span class="ml-1 font-medium" :class="{ 'text-red-600': isLowStock(ingredient) }">
                                {{ formatNumber(ingredient.current_stock) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-muted-foreground">จุดสั่งซื้อ:</span>
                            <span class="ml-1 font-medium">{{ ingredient.reorder_point ? formatNumber(ingredient.reorder_point) : '-' }}</span>
                        </div>
                    </div>
                </div>
                <div v-if="ingredients.length === 0" class="rounded-lg border bg-card p-8 text-center text-muted-foreground">
                    ยังไม่มีวัตถุดิบ
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block rounded-lg border bg-card">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="text-left py-3 px-4 font-medium text-sm">ชื่อ</th>
                                <th class="text-left py-3 px-4 font-medium text-sm hidden lg:table-cell">ซัพพลายเออร์</th>
                                <th class="text-left py-3 px-4 font-medium text-sm">หน่วย</th>
                                <th class="text-right py-3 px-4 font-medium text-sm">ราคา/หน่วย</th>
                                <th class="text-right py-3 px-4 font-medium text-sm">สต็อคปัจจุบัน</th>
                                <th class="text-right py-3 px-4 font-medium text-sm hidden lg:table-cell">จุดสั่งซื้อ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ingredient in ingredients" :key="ingredient.id" class="border-b hover:bg-muted/50 transition-colors">
                                <td class="py-3 px-4">
                                    <span class="font-medium">{{ ingredient.name }}</span>
                                </td>
                                <td class="py-3 px-4 text-muted-foreground hidden lg:table-cell">{{ ingredient.supplier?.name || '-' }}</td>
                                <td class="py-3 px-4 text-sm">{{ ingredient.purchase_unit }}</td>
                                <td class="py-3 px-4 text-right text-sm">{{ formatCurrency(ingredient.cost_per_unit) }}</td>
                                <td class="py-3 px-4 text-right">
                                    <span :class="{ 'text-red-600 font-semibold': isLowStock(ingredient) }">
                                        {{ formatNumber(ingredient.current_stock) }}
                                    </span>
                                    <span v-if="isLowStock(ingredient)" class="ml-1 text-xs text-red-500">(ต่ำ)</span>
                                </td>
                                <td class="py-3 px-4 text-right text-muted-foreground text-sm hidden lg:table-cell">
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
