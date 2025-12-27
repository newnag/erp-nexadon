<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Head, Link } from '@inertiajs/vue3';
import { Package, TrendingDown, TrendingUp, AlertTriangle, ArrowRight, History } from 'lucide-vue-next';

interface Ingredient {
    id: number;
    name: string;
    purchase_unit: string;
    cost_per_unit: number;
    current_stock: number;
    reorder_point: number;
    stock_status: 'low' | 'medium' | 'normal';
    supplier?: { name: string };
}

interface Transaction {
    id: number;
    type: string;
    quantity: number;
    unit_cost: number;
    transaction_date: string;
    notes: string;
    ingredient: { name: string; purchase_unit: string };
}

interface Props {
    summary: {
        totalItems: number;
        totalValue: number;
        lowStockItems: number;
    };
    lowStockIngredients: Ingredient[];
    ingredients: Ingredient[];
    recentTransactions: Transaction[];
    monthlyMovement: Array<{
        month: string;
        stock_in: number;
        stock_out: number;
    }>;
}

defineProps<Props>();

const breadcrumbs = [
    { title: 'จัดการสต็อค', href: '/stock' },
];

const formatNumber = (num: number) => {
    return new Intl.NumberFormat('th-TH').format(num);
};

const formatCurrency = (num: number) => {
    return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(num);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
        purchase: 'ซื้อเข้า',
        usage: 'เบิกใช้',
        waste: 'เสียหาย',
        adjustment_in: 'ปรับเพิ่ม',
        adjustment_out: 'ปรับลด',
    };
    return labels[type] || type;
};

const getTypeColor = (type: string) => {
    if (['purchase', 'adjustment_in'].includes(type)) return 'bg-green-100 text-green-800';
    return 'bg-red-100 text-red-800';
};

const getStockStatusBadge = (status: string) => {
    switch (status) {
        case 'low':
            return 'destructive';
        case 'medium':
            return 'secondary';
        default:
            return 'outline';
    }
};
</script>

<template>
    <Head title="จัดการสต็อค" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold">จัดการสต็อค</h1>
                    <p class="text-muted-foreground">ภาพรวมและการจัดการสต็อควัตถุดิบ</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/stock/in">
                        <Button variant="default">
                            <TrendingUp class="w-4 h-4 mr-2" />
                            รับสินค้าเข้า
                        </Button>
                    </Link>
                    <Link href="/stock/out">
                        <Button variant="outline">
                            <TrendingDown class="w-4 h-4 mr-2" />
                            เบิกสินค้าออก
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">รายการวัตถุดิบทั้งหมด</CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatNumber(summary.totalItems) }}</div>
                        <p class="text-xs text-muted-foreground">รายการ</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">มูลค่าสต็อคคงเหลือ</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ formatCurrency(summary.totalValue) }}</div>
                        <p class="text-xs text-muted-foreground">บาท</p>
                    </CardContent>
                </Card>

                <Card :class="{ 'border-red-300 bg-red-50': summary.lowStockItems > 0 }">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">สินค้าใกล้หมด</CardTitle>
                        <AlertTriangle :class="['h-4 w-4', summary.lowStockItems > 0 ? 'text-red-500' : 'text-muted-foreground']" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold" :class="{ 'text-red-600': summary.lowStockItems > 0 }">
                            {{ formatNumber(summary.lowStockItems) }}
                        </div>
                        <p class="text-xs text-muted-foreground">รายการต้องสั่งซื้อ</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Low Stock Alert -->
                <Card v-if="lowStockIngredients.length > 0" class="border-red-200">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-red-600">
                            <AlertTriangle class="w-5 h-5" />
                            แจ้งเตือนสต็อคต่ำ
                        </CardTitle>
                        <CardDescription>วัตถุดิบที่ต้องสั่งซื้อเพิ่ม</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="item in lowStockIngredients"
                                :key="item.id"
                                class="flex justify-between items-center p-3 bg-red-50 rounded-lg"
                            >
                                <div>
                                    <p class="font-medium">{{ item.name }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        คงเหลือ: {{ formatNumber(item.current_stock) }} {{ item.purchase_unit }}
                                    </p>
                                </div>
                                <Badge variant="destructive">
                                    ต่ำกว่า {{ formatNumber(item.reorder_point) }}
                                </Badge>
                            </div>
                        </div>
                        <Link href="/stock/in" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:underline">
                            สั่งซื้อเพิ่มเติม <ArrowRight class="w-4 h-4 ml-1" />
                        </Link>
                    </CardContent>
                </Card>

                <!-- Recent Transactions -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <History class="w-5 h-5" />
                            การเคลื่อนไหวล่าสุด
                        </CardTitle>
                        <CardDescription>รายการเข้า-ออกสต็อค 10 รายการล่าสุด</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="tx in recentTransactions"
                                :key="tx.id"
                                class="flex justify-between items-center py-2 border-b last:border-0"
                            >
                                <div class="flex-1">
                                    <p class="font-medium text-sm">{{ tx.ingredient.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatDate(tx.transaction_date) }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="['text-sm font-medium', tx.type.includes('adjustment_out') || tx.type === 'usage' || tx.type === 'waste' ? 'text-red-600' : 'text-green-600']">
                                        {{ tx.type.includes('adjustment_out') || tx.type === 'usage' || tx.type === 'waste' ? '-' : '+' }}{{ formatNumber(tx.quantity) }}
                                    </span>
                                    <Badge :class="getTypeColor(tx.type)" variant="secondary">
                                        {{ getTypeLabel(tx.type) }}
                                    </Badge>
                                </div>
                            </div>
                        </div>
                        <Link href="/stock/history" class="mt-4 inline-flex items-center text-sm text-blue-600 hover:underline">
                            ดูประวัติทั้งหมด <ArrowRight class="w-4 h-4 ml-1" />
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <!-- Stock Table -->
            <Card>
                <CardHeader>
                    <CardTitle>รายการวัตถุดิบทั้งหมด</CardTitle>
                    <CardDescription>สถานะสต็อควัตถุดิบปัจจุบัน</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 font-medium">วัตถุดิบ</th>
                                    <th class="text-left py-3 px-4 font-medium">ซัพพลายเออร์</th>
                                    <th class="text-right py-3 px-4 font-medium">สต็อคปัจจุบัน</th>
                                    <th class="text-right py-3 px-4 font-medium">จุดสั่งซื้อ</th>
                                    <th class="text-right py-3 px-4 font-medium">ราคา/หน่วย</th>
                                    <th class="text-right py-3 px-4 font-medium">มูลค่า</th>
                                    <th class="text-center py-3 px-4 font-medium">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in ingredients" :key="item.id" class="border-b hover:bg-muted/50">
                                    <td class="py-3 px-4">
                                        <span class="font-medium">{{ item.name }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-muted-foreground">
                                        {{ item.supplier?.name || '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        {{ formatNumber(item.current_stock) }} {{ item.purchase_unit }}
                                    </td>
                                    <td class="py-3 px-4 text-right text-muted-foreground">
                                        {{ formatNumber(item.reorder_point || 0) }}
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        {{ formatCurrency(item.cost_per_unit) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-medium">
                                        {{ formatCurrency(item.current_stock * item.cost_per_unit) }}
                                    </td>
                                    <td class="py-3 px-4 text-center">
                                        <Badge :variant="getStockStatusBadge(item.stock_status)">
                                            {{ item.stock_status === 'low' ? 'ต่ำ' : item.stock_status === 'medium' ? 'ปานกลาง' : 'ปกติ' }}
                                        </Badge>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Quick Actions -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Link href="/stock/in">
                    <Card class="hover:bg-muted/50 cursor-pointer transition-colors">
                        <CardContent class="flex flex-col items-center justify-center py-6">
                            <TrendingUp class="w-8 h-8 text-green-600 mb-2" />
                            <span class="font-medium">รับสินค้าเข้า</span>
                        </CardContent>
                    </Card>
                </Link>
                <Link href="/stock/out">
                    <Card class="hover:bg-muted/50 cursor-pointer transition-colors">
                        <CardContent class="flex flex-col items-center justify-center py-6">
                            <TrendingDown class="w-8 h-8 text-red-600 mb-2" />
                            <span class="font-medium">เบิกสินค้าออก</span>
                        </CardContent>
                    </Card>
                </Link>
                <Link href="/stock/adjustment">
                    <Card class="hover:bg-muted/50 cursor-pointer transition-colors">
                        <CardContent class="flex flex-col items-center justify-center py-6">
                            <Package class="w-8 h-8 text-blue-600 mb-2" />
                            <span class="font-medium">ปรับปรุงสต็อค</span>
                        </CardContent>
                    </Card>
                </Link>
                <Link href="/stock/history">
                    <Card class="hover:bg-muted/50 cursor-pointer transition-colors">
                        <CardContent class="flex flex-col items-center justify-center py-6">
                            <History class="w-8 h-8 text-purple-600 mb-2" />
                            <span class="font-medium">ประวัติทั้งหมด</span>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
