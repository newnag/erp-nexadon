<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, TrendingDown, ArrowLeft, AlertTriangle } from 'lucide-vue-next';
import { computed } from 'vue';

interface Ingredient {
    id: number;
    name: string;
    purchase_unit: string;
    cost_per_unit: number;
    current_stock: number;
    supplier?: { name: string };
}

interface TransactionType {
    value: string;
    label: string;
}

interface Props {
    ingredients: Ingredient[];
    transactionTypes: TransactionType[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'จัดการสต็อค', href: '/stock' },
    { title: 'เบิกสินค้าออก', href: '/stock/out' },
];

interface StockItem {
    ingredient_id: string;
    quantity: string;
    type: string;
    notes: string;
}

const form = useForm({
    transaction_date: new Date().toISOString().split('T')[0],
    items: [
        { ingredient_id: '', quantity: '', type: 'usage', notes: '' }
    ] as StockItem[],
});

const addItem = () => {
    form.items.push({ ingredient_id: '', quantity: '', type: 'usage', notes: '' });
};

const removeItem = (index: number) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const getIngredient = (id: string) => {
    return props.ingredients.find(i => i.id === Number(id));
};

const isOverStock = (item: StockItem) => {
    if (!item.ingredient_id || !item.quantity) return false;
    const ingredient = getIngredient(item.ingredient_id);
    if (!ingredient) return false;
    return parseFloat(item.quantity) > ingredient.current_stock;
};

const formatNumber = (num: number) => {
    return new Intl.NumberFormat('th-TH').format(num);
};

const totalCost = computed(() => {
    return form.items.reduce((sum, item) => {
        const ingredient = getIngredient(item.ingredient_id);
        if (!ingredient) return sum;
        const qty = parseFloat(item.quantity) || 0;
        return sum + (qty * ingredient.cost_per_unit);
    }, 0);
});

const formatCurrency = (num: number) => {
    return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(num);
};

const hasError = computed(() => {
    return form.items.some(item => isOverStock(item));
});

const submit = () => {
    if (hasError.value) return;
    
    form.post('/stock/out', {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="เบิกสินค้าออกจากสต็อค" />

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
                        <TrendingDown class="w-6 h-6 text-red-600" />
                        เบิกสินค้าออกจากสต็อค
                    </h1>
                    <p class="text-muted-foreground">บันทึกการเบิกวัตถุดิบออกจากคลัง</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Transaction Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>ข้อมูลการเบิก</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="transaction_date">วันที่เบิก *</Label>
                                <Input
                                    id="transaction_date"
                                    type="date"
                                    v-model="form.transaction_date"
                                    required
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Items -->
                <Card>
                    <CardHeader>
                        <CardTitle>รายการเบิก</CardTitle>
                        <CardDescription>เลือกวัตถุดิบที่ต้องการเบิกออก</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                :class="[
                                    'grid grid-cols-1 md:grid-cols-12 gap-4 p-4 rounded-lg',
                                    isOverStock(item) ? 'bg-red-50 border border-red-200' : 'bg-muted/30'
                                ]"
                            >
                                <div class="md:col-span-4 space-y-2">
                                    <Label>วัตถุดิบ *</Label>
                                    <select
                                        v-model="item.ingredient_id"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        required
                                    >
                                        <option value="">-- เลือกวัตถุดิบ --</option>
                                        <option v-for="ing in ingredients" :key="ing.id" :value="ing.id">
                                            {{ ing.name }} (คงเหลือ: {{ formatNumber(ing.current_stock) }} {{ ing.purchase_unit }})
                                        </option>
                                    </select>
                                    <p v-if="item.ingredient_id" class="text-xs text-muted-foreground">
                                        คงเหลือ: <strong>{{ formatNumber(getIngredient(item.ingredient_id)?.current_stock || 0) }}</strong>
                                        {{ getIngredient(item.ingredient_id)?.purchase_unit }}
                                    </p>
                                </div>
                                <div class="md:col-span-2 space-y-2">
                                    <Label>จำนวน *</Label>
                                    <Input
                                        type="number"
                                        step="0.0001"
                                        min="0.0001"
                                        v-model="item.quantity"
                                        placeholder="0"
                                        required
                                        :class="{ 'border-red-500': isOverStock(item) }"
                                    />
                                    <p v-if="isOverStock(item)" class="text-xs text-red-600 flex items-center gap-1">
                                        <AlertTriangle class="w-3 h-3" />
                                        เกินสต็อคคงเหลือ
                                    </p>
                                </div>
                                <div class="md:col-span-2 space-y-2">
                                    <Label>ประเภท *</Label>
                                    <select
                                        v-model="item.type"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        required
                                    >
                                        <option v-for="type in transactionTypes" :key="type.value" :value="type.value">
                                            {{ type.label }}
                                        </option>
                                    </select>
                                </div>
                                <div class="md:col-span-3 space-y-2">
                                    <Label>หมายเหตุ</Label>
                                    <Input
                                        v-model="item.notes"
                                        placeholder="หมายเหตุ"
                                    />
                                </div>
                                <div class="md:col-span-1 flex items-end">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        @click="removeItem(index)"
                                        :disabled="form.items.length === 1"
                                        class="text-red-600 hover:text-red-700 hover:bg-red-50"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                            </div>

                            <Button type="button" variant="outline" @click="addItem" class="w-full">
                                <Plus class="w-4 h-4 mr-2" />
                                เพิ่มรายการ
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Summary & Submit -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                            <div class="text-lg">
                                <span class="text-muted-foreground">มูลค่าต้นทุนรวม:</span>
                                <span class="font-bold text-xl ml-2 text-red-600">{{ formatCurrency(totalCost) }}</span>
                            </div>
                            <div class="flex gap-2">
                                <Link href="/stock">
                                    <Button type="button" variant="outline">ยกเลิก</Button>
                                </Link>
                                <Button type="submit" :disabled="form.processing || hasError" variant="destructive">
                                    <TrendingDown class="w-4 h-4 mr-2" />
                                    บันทึกเบิกสินค้า
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </div>
    </AppLayout>
</template>
