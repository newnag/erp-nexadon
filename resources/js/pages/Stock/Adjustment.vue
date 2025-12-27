<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Package, ArrowLeft, RefreshCw } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Ingredient {
    id: number;
    name: string;
    purchase_unit: string;
    cost_per_unit: number;
    current_stock: number;
    supplier?: { name: string };
}

interface Props {
    ingredients: Ingredient[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'จัดการสต็อค', href: '/stock' },
    { title: 'ปรับปรุงสต็อค', href: '/stock/adjustment' },
];

const form = useForm({
    ingredient_id: '',
    actual_stock: '',
    notes: '',
    transaction_date: new Date().toISOString().split('T')[0],
});

const selectedIngredient = ref<Ingredient | null>(null);

watch(() => form.ingredient_id, (id) => {
    selectedIngredient.value = props.ingredients.find(i => i.id === Number(id)) || null;
    if (selectedIngredient.value) {
        form.actual_stock = String(selectedIngredient.value.current_stock);
    }
});

const difference = computed(() => {
    if (!selectedIngredient.value || form.actual_stock === '') return null;
    return parseFloat(form.actual_stock) - selectedIngredient.value.current_stock;
});

const formatNumber = (num: number) => {
    return new Intl.NumberFormat('th-TH').format(num);
};

const formatCurrency = (num: number) => {
    return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(num);
};

const submit = () => {
    form.post('/stock/adjustment', {
        onSuccess: () => {
            form.reset();
            selectedIngredient.value = null;
        },
    });
};
</script>

<template>
    <Head title="ปรับปรุงสต็อค" />

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
                        <Package class="w-6 h-6 text-blue-600" />
                        ปรับปรุงสต็อค
                    </h1>
                    <p class="text-muted-foreground">ปรับยอดสต็อคจากการตรวจนับจริง</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Adjustment Form -->
                    <Card>
                        <CardHeader>
                            <CardTitle>ข้อมูลการปรับปรุง</CardTitle>
                            <CardDescription>เลือกวัตถุดิบและระบุจำนวนที่ตรวจนับได้</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="ingredient_id">วัตถุดิบ *</Label>
                                <select
                                    id="ingredient_id"
                                    v-model="form.ingredient_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    required
                                >
                                    <option value="">-- เลือกวัตถุดิบ --</option>
                                    <option v-for="ing in ingredients" :key="ing.id" :value="ing.id">
                                        {{ ing.name }} ({{ ing.purchase_unit }})
                                    </option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <Label for="transaction_date">วันที่ตรวจนับ *</Label>
                                <Input
                                    id="transaction_date"
                                    type="date"
                                    v-model="form.transaction_date"
                                    required
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="actual_stock">
                                    จำนวนที่ตรวจนับได้ *
                                    <span v-if="selectedIngredient" class="text-muted-foreground font-normal">
                                        ({{ selectedIngredient.purchase_unit }})
                                    </span>
                                </Label>
                                <Input
                                    id="actual_stock"
                                    type="number"
                                    step="0.0001"
                                    min="0"
                                    v-model="form.actual_stock"
                                    placeholder="0"
                                    required
                                    :disabled="!selectedIngredient"
                                />
                            </div>

                            <div class="space-y-2">
                                <Label for="notes">หมายเหตุ</Label>
                                <Textarea
                                    id="notes"
                                    v-model="form.notes"
                                    placeholder="เหตุผลในการปรับปรุง..."
                                    :rows="3"
                                />
                            </div>

                            <div class="flex gap-2 pt-4">
                                <Link href="/stock" class="flex-1">
                                    <Button type="button" variant="outline" class="w-full">ยกเลิก</Button>
                                </Link>
                                <Button type="submit" :disabled="form.processing || !selectedIngredient" class="flex-1">
                                    <RefreshCw class="w-4 h-4 mr-2" />
                                    บันทึกการปรับปรุง
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>

                <!-- Preview -->
                <div class="space-y-6">
                    <Card v-if="selectedIngredient">
                        <CardHeader>
                            <CardTitle>{{ selectedIngredient.name }}</CardTitle>
                            <CardDescription>ข้อมูลสต็อคปัจจุบัน</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-muted/50 p-4 rounded-lg">
                                    <p class="text-sm text-muted-foreground">สต็อคในระบบ</p>
                                    <p class="text-2xl font-bold">
                                        {{ formatNumber(selectedIngredient.current_stock) }}
                                    </p>
                                    <p class="text-sm text-muted-foreground">{{ selectedIngredient.purchase_unit }}</p>
                                </div>
                                <div class="bg-muted/50 p-4 rounded-lg">
                                    <p class="text-sm text-muted-foreground">ราคาต่อหน่วย</p>
                                    <p class="text-2xl font-bold">
                                        {{ formatCurrency(selectedIngredient.cost_per_unit) }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="selectedIngredient.supplier" class="text-sm text-muted-foreground">
                                ซัพพลายเออร์: {{ selectedIngredient.supplier.name }}
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-if="difference !== null && difference !== 0" :class="difference > 0 ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50'">
                        <CardHeader>
                            <CardTitle :class="difference > 0 ? 'text-green-700' : 'text-red-700'">
                                ผลการปรับปรุง
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="text-center">
                                <p class="text-sm text-muted-foreground mb-2">ส่วนต่าง</p>
                                <p :class="['text-4xl font-bold', difference > 0 ? 'text-green-600' : 'text-red-600']">
                                    {{ difference > 0 ? '+' : '' }}{{ formatNumber(difference) }}
                                </p>
                                <p class="text-sm text-muted-foreground">{{ selectedIngredient?.purchase_unit }}</p>
                                <p class="mt-4 text-sm">
                                    <span v-if="difference > 0">จะทำการ <strong class="text-green-600">เพิ่มสต็อค</strong></span>
                                    <span v-else>จะทำการ <strong class="text-red-600">ลดสต็อค</strong></span>
                                </p>
                                <p class="text-sm text-muted-foreground mt-2">
                                    มูลค่า: {{ formatCurrency(Math.abs(difference) * (selectedIngredient?.cost_per_unit || 0)) }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card v-else-if="difference === 0">
                        <CardContent class="py-8 text-center">
                            <p class="text-muted-foreground">สต็อคตรงกับระบบ ไม่ต้องปรับปรุง</p>
                        </CardContent>
                    </Card>

                    <Card v-else>
                        <CardContent class="py-8 text-center">
                            <Package class="w-12 h-12 mx-auto text-muted-foreground mb-4" />
                            <p class="text-muted-foreground">เลือกวัตถุดิบเพื่อดูข้อมูล</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
