<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Head, Link, router } from '@inertiajs/vue3';
import { History, ArrowLeft, Search, Filter } from 'lucide-vue-next';
import { ref } from 'vue';

interface Transaction {
    id: number;
    type: string;
    quantity: number;
    unit_cost: number;
    transaction_date: string;
    notes: string;
    ingredient: {
        id: number;
        name: string;
        purchase_unit: string;
    };
}

interface Ingredient {
    id: number;
    name: string;
}

interface Props {
    transactions: {
        data: Transaction[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
    ingredients: Ingredient[];
    filters: {
        ingredient_id?: string;
        type?: string;
        date_from?: string;
        date_to?: string;
    };
    transactionTypes: Record<string, string>;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'จัดการสต็อค', href: '/stock' },
    { title: 'ประวัติการเคลื่อนไหว', href: '/stock/history' },
];

const filters = ref({
    ingredient_id: props.filters.ingredient_id || '',
    type: props.filters.type || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

const applyFilters = () => {
    router.get('/stock/history', {
        ...filters.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filters.value = {
        ingredient_id: '',
        type: '',
        date_from: '',
        date_to: '',
    };
    router.get('/stock/history');
};

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
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getTypeColor = (type: string) => {
    switch (type) {
        case 'purchase':
        case 'adjustment_in':
            return 'bg-green-100 text-green-800';
        case 'usage':
        case 'adjustment_out':
        case 'waste':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const isStockIn = (type: string) => {
    return ['purchase', 'adjustment_in'].includes(type);
};
</script>

<template>
    <Head title="ประวัติการเคลื่อนไหวสต็อค" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/stock">
                    <Button variant="ghost" size="icon">
                        <ArrowLeft class="w-5 h-5" />
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <History class="w-6 h-6 text-purple-600" />
                        ประวัติการเคลื่อนไหวสต็อค
                    </h1>
                    <p class="text-muted-foreground">รายการรับ-เบิกวัตถุดิบทั้งหมด</p>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Filter class="w-4 h-4" />
                        ตัวกรอง
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="space-y-2">
                            <Label>วัตถุดิบ</Label>
                            <select
                                v-model="filters.ingredient_id"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="">-- ทั้งหมด --</option>
                                <option v-for="ing in ingredients" :key="ing.id" :value="ing.id">
                                    {{ ing.name }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label>ประเภท</Label>
                            <select
                                v-model="filters.type"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="">-- ทั้งหมด --</option>
                                <option v-for="(label, value) in transactionTypes" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label>จากวันที่</Label>
                            <Input type="date" v-model="filters.date_from" />
                        </div>
                        <div class="space-y-2">
                            <Label>ถึงวันที่</Label>
                            <Input type="date" v-model="filters.date_to" />
                        </div>
                        <div class="flex items-end gap-2">
                            <Button @click="applyFilters" class="flex-1">
                                <Search class="w-4 h-4 mr-2" />
                                ค้นหา
                            </Button>
                            <Button variant="outline" @click="clearFilters">ล้าง</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Results -->
            <Card>
                <CardHeader>
                    <CardTitle>รายการทั้งหมด {{ formatNumber(transactions.total) }} รายการ</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 font-medium">วันที่</th>
                                    <th class="text-left py-3 px-4 font-medium">วัตถุดิบ</th>
                                    <th class="text-left py-3 px-4 font-medium">ประเภท</th>
                                    <th class="text-right py-3 px-4 font-medium">จำนวน</th>
                                    <th class="text-right py-3 px-4 font-medium">ราคา/หน่วย</th>
                                    <th class="text-right py-3 px-4 font-medium">มูลค่า</th>
                                    <th class="text-left py-3 px-4 font-medium">หมายเหตุ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="tx in transactions.data" :key="tx.id" class="border-b hover:bg-muted/50">
                                    <td class="py-3 px-4 text-sm">
                                        {{ formatDate(tx.transaction_date) }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="font-medium">{{ tx.ingredient.name }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <Badge :class="getTypeColor(tx.type)">
                                            {{ transactionTypes[tx.type] || tx.type }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span :class="isStockIn(tx.type) ? 'text-green-600' : 'text-red-600'" class="font-medium">
                                            {{ isStockIn(tx.type) ? '+' : '-' }}{{ formatNumber(tx.quantity) }}
                                        </span>
                                        <span class="text-muted-foreground text-sm ml-1">{{ tx.ingredient.purchase_unit }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        {{ formatCurrency(tx.unit_cost) }}
                                    </td>
                                    <td class="py-3 px-4 text-right font-medium">
                                        {{ formatCurrency(tx.quantity * tx.unit_cost) }}
                                    </td>
                                    <td class="py-3 px-4 text-sm text-muted-foreground max-w-xs truncate">
                                        {{ tx.notes || '-' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="transactions.last_page > 1" class="flex justify-center gap-2 mt-6">
                        <template v-for="link in transactions.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="px-3 py-1 rounded border text-sm"
                                :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                            >
                                <!-- eslint-disable-next-line vue/no-v-html -->
                                <span v-html="link.label" />
                            </Link>
                            <!-- eslint-disable-next-line vue/no-v-html -->
                            <span v-else class="px-3 py-1 text-sm text-muted-foreground" v-html="link.label" />
                        </template>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
