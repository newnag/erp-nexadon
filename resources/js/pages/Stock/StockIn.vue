<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, TrendingUp, ArrowLeft } from 'lucide-vue-next';
import { computed, watch } from 'vue';

interface Ingredient {
    id: number;
    name: string;
    purchase_unit: string;
    cost_per_unit: number;
    current_stock: number;
    supplier_id?: number | null;
    supplier?: { id: number; name: string };
}

interface Supplier {
    id: number;
    name: string;
}

interface Props {
    ingredients: Ingredient[];
    suppliers: Supplier[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'จัดการสต็อค', href: '/stock' },
    { title: 'รับสินค้าเข้า', href: '/stock/in' },
];

interface StockItem {
    ingredient_id: string;
    quantity: string;
    unit_cost: string;
    notes: string;
}

const form = useForm({
    transaction_date: new Date().toISOString().split('T')[0],
    reference_number: '',
    supplier_id: '',
    items: [
        { ingredient_id: '', quantity: '', unit_cost: '', notes: '' }
    ] as StockItem[],
});

const addItem = () => {
    form.items.push({ ingredient_id: '', quantity: '', unit_cost: '', notes: '' });
};

const removeItem = (index: number) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
    }
};

const getIngredient = (id: string) => {
    return props.ingredients.find(i => i.id === Number(id));
};

// Filter ingredients by selected supplier
const filteredIngredients = computed(() => {
    if (!form.supplier_id) {
        return props.ingredients;
    }
    return props.ingredients.filter(ing => 
        ing.supplier_id === Number(form.supplier_id) || ing.supplier?.id === Number(form.supplier_id)
    );
});

// Clear ingredient selections when supplier changes
watch(() => form.supplier_id, () => {
    form.items.forEach(item => {
        // Check if selected ingredient still valid for this supplier
        if (item.ingredient_id) {
            const stillValid = filteredIngredients.value.some(ing => ing.id === Number(item.ingredient_id));
            if (!stillValid) {
                item.ingredient_id = '';
                item.unit_cost = '';
            }
        }
    });
});

const onIngredientChange = (index: number) => {
    const ingredient = getIngredient(form.items[index].ingredient_id);
    if (ingredient) {
        form.items[index].unit_cost = String(ingredient.cost_per_unit);
    }
};

const totalAmount = computed(() => {
    return form.items.reduce((sum, item) => {
        const qty = parseFloat(item.quantity) || 0;
        const cost = parseFloat(item.unit_cost) || 0;
        return sum + (qty * cost);
    }, 0);
});

const formatCurrency = (num: number) => {
    return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(num);
};

const submit = () => {
    form.post('/stock/in', {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head title="รับสินค้าเข้าสต็อค" />

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
                        <TrendingUp class="w-6 h-6 text-green-600" />
                        รับสินค้าเข้าสต็อค
                    </h1>
                    <p class="text-muted-foreground">บันทึกการรับวัตถุดิบเข้าคลัง</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Transaction Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>ข้อมูลการรับสินค้า</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <Label for="transaction_date">วันที่รับสินค้า *</Label>
                                <Input
                                    id="transaction_date"
                                    type="date"
                                    v-model="form.transaction_date"
                                    required
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="reference_number">เลขที่อ้างอิง</Label>
                                <Input
                                    id="reference_number"
                                    v-model="form.reference_number"
                                    placeholder="เลขที่ใบส่งของ, PO"
                                />
                            </div>
                            <div class="space-y-2">
                                <Label for="supplier_id">ซัพพลายเออร์</Label>
                                <select
                                    id="supplier_id"
                                    v-model="form.supplier_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                >
                                    <option value="">-- เลือกซัพพลายเออร์ --</option>
                                    <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                        {{ supplier.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Items -->
                <Card>
                    <CardHeader>
                        <CardTitle>รายการสินค้า</CardTitle>
                        <CardDescription>เพิ่มวัตถุดิบที่ต้องการรับเข้าสต็อค</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div
                                v-for="(item, index) in form.items"
                                :key="index"
                                class="grid grid-cols-1 md:grid-cols-12 gap-4 p-4 bg-muted/30 rounded-lg"
                            >
                                <div class="md:col-span-4 space-y-2">
                                    <Label>วัตถุดิบ *</Label>
                                    <select
                                        v-model="item.ingredient_id"
                                        @change="onIngredientChange(index)"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                        required
                                    >
                                        <option value="">-- เลือกวัตถุดิบ --</option>
                                        <option v-for="ing in filteredIngredients" :key="ing.id" :value="ing.id">
                                            {{ ing.name }} ({{ ing.purchase_unit }})
                                        </option>
                                    </select>
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
                                    />
                                    <p v-if="item.ingredient_id" class="text-xs text-muted-foreground">
                                        {{ getIngredient(item.ingredient_id)?.purchase_unit }}
                                    </p>
                                </div>
                                <div class="md:col-span-2 space-y-2">
                                    <Label>ราคา/หน่วย</Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        v-model="item.unit_cost"
                                        placeholder="0.00"
                                    />
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
                                <span class="text-muted-foreground">มูลค่ารวม:</span>
                                <span class="font-bold text-xl ml-2">{{ formatCurrency(totalAmount) }}</span>
                            </div>
                            <div class="flex gap-2">
                                <Link href="/stock">
                                    <Button type="button" variant="outline">ยกเลิก</Button>
                                </Link>
                                <Button type="submit" :disabled="form.processing">
                                    <TrendingUp class="w-4 h-4 mr-2" />
                                    บันทึกรับสินค้า
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </div>
    </AppLayout>
</template>
