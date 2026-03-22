<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { computed } from 'vue';

interface Category {
    id: number;
    name: string;
    type: 'income' | 'expense';
    color: string;
}

const props = defineProps<{
    categories: Category[];
}>();

const breadcrumbs = [
    {
        title: 'การเงิน',
        href: '/finance',
    },
    {
        title: 'บันทึกรายการ',
        href: '/finance/create',
    },
];

const form = useForm({
    type: 'expense' as 'income' | 'expense',
    category_id: '',
    amount: '',
    description: '',
    notes: '',
    transaction_date: new Date().toISOString().split('T')[0],
    reference_number: '',
    payment_method: 'cash',
});

const filteredCategories = computed(() => {
    return props.categories.filter(cat => cat.type === form.type);
});

const submit = () => {
    form.post('/finance', {
        onSuccess: () => {
            form.reset();
        },
    });
};

// Watch for type change to reset category
const onTypeChange = () => {
    form.category_id = '';
};
</script>

<template>
    <Head title="บันทึกรายรับรายจ่าย" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 p-3 sm:p-4 md:p-6">
            <div class="max-w-3xl mx-auto w-full">
                <div class="flex items-center gap-3 mb-4 sm:mb-6">
                    <Link href="/finance">
                        <Button variant="ghost" size="icon" class="h-8 w-8 sm:h-10 sm:w-10">
                            <ArrowLeft class="w-4 h-4 sm:w-5 sm:h-5" />
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-lg sm:text-2xl font-bold">บันทึกรายรับรายจ่าย</h1>
                        <p class="text-sm text-muted-foreground">เพิ่มรายการรายรับหรือรายจ่ายใหม่</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>ข้อมูลรายการ</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Transaction Type -->
                            <div class="space-y-2">
                                <Label>ประเภทรายการ</Label>
                                <div class="flex gap-4">
                                    <label class="flex items-center">
                                        <input type="radio" v-model="form.type" value="income" @change="onTypeChange" class="form-radio text-green-500" />
                                        <span class="ml-2 text-green-600 dark:text-green-400 font-medium">รายรับ</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" v-model="form.type" value="expense" @change="onTypeChange" class="form-radio text-red-500" />
                                        <span class="ml-2 text-red-600 dark:text-red-400 font-medium">รายจ่าย</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="space-y-2">
                                <Label>หมวดหมู่</Label>
                                <select
                                    v-model="form.category_id"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    required
                                >
                                    <option value="">เลือกหมวดหมู่</option>
                                    <option v-for="category in filteredCategories" :key="category.id" :value="category.id">
                                        {{ category.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.category_id" class="text-destructive text-xs mt-1">{{ form.errors.category_id }}</div>
                                <p v-if="filteredCategories.length === 0" class="text-orange-500 text-xs mt-1">
                                    ไม่มีหมวดหมู่สำหรับประเภทนี้ กรุณา <Link href="/finance/categories" class="underline">สร้างหมวดหมู่</Link> ก่อน
                                </p>
                            </div>

                            <!-- Amount -->
                            <div class="space-y-2">
                                <Label>จำนวนเงิน (บาท)</Label>
                                <Input v-model="form.amount" type="number" step="0.01" min="0.01" required placeholder="0.00" />
                                <div v-if="form.errors.amount" class="text-destructive text-xs mt-1">{{ form.errors.amount }}</div>
                            </div>

                            <!-- Description -->
                            <div class="space-y-2">
                                <Label>รายละเอียด</Label>
                                <Input v-model="form.description" type="text" required placeholder="เช่น ค่าอาหาร, รายได้จากการขาย" />
                                <div v-if="form.errors.description" class="text-destructive text-xs mt-1">{{ form.errors.description }}</div>
                            </div>

                            <!-- Transaction Date -->
                            <div class="space-y-2">
                                <Label>วันที่</Label>
                                <Input v-model="form.transaction_date" type="date" required />
                                <div v-if="form.errors.transaction_date" class="text-destructive text-xs mt-1">{{ form.errors.transaction_date }}</div>
                            </div>

                            <!-- Payment Method -->
                            <div class="space-y-2">
                                <Label>วิธีชำระเงิน</Label>
                                <select
                                    v-model="form.payment_method"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                >
                                    <option value="cash">เงินสด</option>
                                    <option value="transfer">โอนเงิน</option>
                                    <option value="credit_card">บัตรเครดิต</option>
                                    <option value="cheque">เช็ค</option>
                                    <option value="other">อื่นๆ</option>
                                </select>
                            </div>

                            <!-- Reference Number -->
                            <div class="space-y-2">
                                <Label>เลขที่อ้างอิง (ไม่บังคับ)</Label>
                                <Input v-model="form.reference_number" type="text" placeholder="เช่น เลขที่ใบเสร็จ" />
                            </div>

                            <!-- Notes -->
                            <div class="space-y-2">
                                <Label>หมายเหตุ (ไม่บังคับ)</Label>
                                <Textarea v-model="form.notes" :rows="3" placeholder="รายละเอียดเพิ่มเติม" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Submit -->
                    <Card>
                        <CardContent class="pt-6">
                            <div class="flex items-center justify-between">
                                <Button as-child variant="outline">
                                    <Link href="/finance">
                                        <ArrowLeft class="mr-1.5 h-4 w-4" /> กลับ
                                    </Link>
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    :variant="form.type === 'income' ? 'default' : 'destructive'"
                                >
                                    <Save class="mr-1.5 h-4 w-4" />
                                    {{ form.processing ? 'กำลังบันทึก...' : 'บันทึกรายการ' }}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
