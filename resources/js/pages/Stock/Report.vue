<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, router } from '@inertiajs/vue3';
import { FileText, ArrowLeft, TrendingUp, TrendingDown, Package } from 'lucide-vue-next';
import { ref } from 'vue';

interface StockItem {
    id: number;
    name: string;
    purchase_unit: string;
    cost_per_unit: number;
    current_stock: number;
    stock_value: number;
    supplier?: { id: number; name: string };
}

interface SupplierSummary {
    supplier_id: number | null;
    item_count: number;
    total_value: number;
    supplier?: { name: string };
}

interface MovementSummary {
    type: string;
    count: number;
    total_quantity: number;
    total_value: number;
}

interface Props {
    stockSummary: StockItem[];
    bySupplier: SupplierSummary[];
    movementSummary: MovementSummary[];
    filters: {
        date_from?: string;
        date_to?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'จัดการสต็อค', href: '/stock' },
    { title: 'รายงาน', href: '/stock/report' },
];

const filters = ref({
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const applyFilters = () => {
    router.get('/stock/report', filters.value, {
        preserveState: true,
    });
};

const formatNumber = (num: number) => {
    return new Intl.NumberFormat('th-TH').format(num);
};

const formatCurrency = (num: number) => {
    return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(num);
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

const isStockIn = (type: string) => ['purchase', 'adjustment_in'].includes(type);

const totalStockValue = props.stockSummary.reduce((sum, item) => sum + (item.stock_value || 0), 0);
const totalStockIn = props.movementSummary.filter(m => isStockIn(m.type)).reduce((sum, m) => sum + (m.total_value || 0), 0);
const totalStockOut = props.movementSummary.filter(m => !isStockIn(m.type)).reduce((sum, m) => sum + (m.total_value || 0), 0);
</script>

<template>
    <Head title="รายงานสต็อค" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/stock">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft class="w-5 h-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold flex items-center gap-2">
                            <FileText class="w-6 h-6 text-blue-600" />
                            รายงานสต็อค
                        </h1>
                        <p class="text-muted-foreground">สรุปข้อมูลสต็อคและการเคลื่อนไหว</p>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">มูลค่าสต็อคคงเหลือ</CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-blue-600">{{ formatCurrency(totalStockValue) }}</div>
                        <p class="text-xs text-muted-foreground">{{ formatNumber(stockSummary.length) }} รายการ</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">มูลค่ารับเข้า</CardTitle>
                        <TrendingUp class="h-4 w-4 text-green-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-green-600">{{ formatCurrency(totalStockIn) }}</div>
                        <p class="text-xs text-muted-foreground">ในช่วงเวลาที่เลือก</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">มูลค่าเบิกออก</CardTitle>
                        <TrendingDown class="h-4 w-4 text-red-600" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold text-red-600">{{ formatCurrency(totalStockOut) }}</div>
                        <p class="text-xs text-muted-foreground">ในช่วงเวลาที่เลือก</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Date Filter -->
            <Card>
                <CardHeader>
                    <CardTitle>ช่วงเวลา</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-4 items-end">
                        <div class="space-y-2">
                            <Label>จากวันที่</Label>
                            <Input type="date" v-model="filters.date_from" />
                        </div>
                        <div class="space-y-2">
                            <Label>ถึงวันที่</Label>
                            <Input type="date" v-model="filters.date_to" />
                        </div>
                        <Button @click="applyFilters">ดูรายงาน</Button>
                    </div>
                </CardContent>
            </Card>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Movement Summary -->
                <Card>
                    <CardHeader>
                        <CardTitle>สรุปการเคลื่อนไหว</CardTitle>
                        <CardDescription>แยกตามประเภทรายการ</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-for="item in movementSummary"
                                :key="item.type"
                                class="flex justify-between items-center p-3 rounded-lg"
                                :class="isStockIn(item.type) ? 'bg-green-50' : 'bg-red-50'"
                            >
                                <div>
                                    <p class="font-medium">{{ getTypeLabel(item.type) }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatNumber(item.count) }} รายการ
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p :class="['font-bold', isStockIn(item.type) ? 'text-green-600' : 'text-red-600']">
                                        {{ formatCurrency(item.total_value || 0) }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatNumber(item.total_quantity || 0) }} หน่วย
                                    </p>
                                </div>
                            </div>
                            <div v-if="movementSummary.length === 0" class="text-center py-8 text-muted-foreground">
                                ไม่มีข้อมูลการเคลื่อนไหว
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- By Supplier -->
                <Card>
                    <CardHeader>
                        <CardTitle>สรุปตามซัพพลายเออร์</CardTitle>
                        <CardDescription>มูลค่าสต็อคแยกตามผู้จำหน่าย</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="item in bySupplier"
                                :key="item.supplier_id || 'none'"
                                class="flex justify-between items-center p-3 bg-muted/30 rounded-lg"
                            >
                                <div>
                                    <p class="font-medium">{{ item.supplier?.name || 'ไม่ระบุ' }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        {{ formatNumber(item.item_count) }} รายการ
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold">{{ formatCurrency(item.total_value || 0) }}</p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Stock Detail Table -->
            <Card>
                <CardHeader>
                    <CardTitle>รายละเอียดสต็อคคงเหลือ</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 font-medium">วัตถุดิบ</th>
                                    <th class="text-left py-3 px-4 font-medium">ซัพพลายเออร์</th>
                                    <th class="text-right py-3 px-4 font-medium">สต็อคคงเหลือ</th>
                                    <th class="text-right py-3 px-4 font-medium">ราคา/หน่วย</th>
                                    <th class="text-right py-3 px-4 font-medium">มูลค่ารวม</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in stockSummary" :key="item.id" class="border-b hover:bg-muted/50">
                                    <td class="py-3 px-4 font-medium">{{ item.name }}</td>
                                    <td class="py-3 px-4 text-muted-foreground">{{ item.supplier?.name || '-' }}</td>
                                    <td class="py-3 px-4 text-right">
                                        {{ formatNumber(item.current_stock) }} {{ item.purchase_unit }}
                                    </td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(item.cost_per_unit) }}</td>
                                    <td class="py-3 px-4 text-right font-bold">{{ formatCurrency(item.stock_value || 0) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-muted/50 font-bold">
                                    <td class="py-3 px-4" colspan="4">รวมทั้งหมด</td>
                                    <td class="py-3 px-4 text-right">{{ formatCurrency(totalStockValue) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
