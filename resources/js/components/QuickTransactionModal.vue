<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface Category {
    id: number;
    name: string;
    type: 'income' | 'expense';
    color: string;
}

interface RecentDescription {
    description: string;
    usage_count: number;
    last_used: string;
}

const props = defineProps<{
    categories: Category[];
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'success'): void;
}>();

const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value),
});

const amountInputRef = ref<HTMLInputElement | null>(null);

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

// Recent descriptions from history
const recentDescriptions = ref<RecentDescription[]>([]);
const isLoadingDescriptions = ref(false);

// Fetch recent descriptions when category changes
const fetchRecentDescriptions = async (categoryId?: string, type?: string) => {
    isLoadingDescriptions.value = true;
    try {
        const params = new URLSearchParams();
        if (categoryId) params.append('category_id', categoryId);
        if (type) params.append('type', type);
        
        const response = await axios.get(`/finance/recent-descriptions?${params.toString()}`);
        recentDescriptions.value = response.data;
    } catch (error) {
        console.error('Failed to fetch recent descriptions:', error);
        recentDescriptions.value = [];
    } finally {
        isLoadingDescriptions.value = false;
    }
};

// Default preset descriptions (fallback when no history)
const defaultPresetDescriptions: Record<string, string[]> = {
    income: [
        'รายได้จากการขาย',
        'รายได้จากบริการ',
        'ดอกเบี้ยรับ',
        'รายได้อื่นๆ',
    ],
    expense: [
        'ค่าวัตถุดิบ',
        'ค่าน้ำ/ค่าไฟ',
        'ค่าเช่า',
        'เงินเดือนพนักงาน',
        'ค่าขนส่ง',
        'ค่าโฆษณา',
        'ค่าซ่อมบำรุง',
        'ค่าใช้จ่ายเบ็ดเตล็ด',
    ],
};

// Computed: Get current category name
const currentCategory = computed(() => {
    if (!form.category_id) return null;
    return props.categories.find(c => c.id.toString() === form.category_id);
});

// Payment methods
const paymentMethods = [
    { value: 'cash', label: 'เงินสด', icon: '💵' },
    { value: 'transfer', label: 'โอนเงิน', icon: '🏦' },
    { value: 'credit_card', label: 'บัตรเครดิต', icon: '💳' },
    { value: 'cheque', label: 'เช็ค', icon: '📝' },
];

// Filtered categories based on type
const filteredCategories = computed(() => {
    return props.categories.filter((cat) => cat.type === form.type);
});

// Watch for type change to reset category and fetch descriptions
watch(
    () => form.type,
    (newType) => {
        form.category_id = '';
        form.description = '';
        fetchRecentDescriptions(undefined, newType);
    },
);

// Watch for category change to auto-fill description and fetch history
watch(
    () => form.category_id,
    (newCategoryId) => {
        if (newCategoryId) {
            const category = props.categories.find(c => c.id.toString() === newCategoryId);
            if (category) {
                // Auto-fill description with category name if empty
                if (!form.description) {
                    form.description = category.name;
                }
                // Fetch recent descriptions for this category
                fetchRecentDescriptions(newCategoryId, form.type);
            }
        }
    },
);

// Auto-focus amount input when modal opens
watch(isOpen, (open) => {
    if (open) {
        setTimeout(() => {
            amountInputRef.value?.focus();
        }, 100);
    }
});

// Reset form when modal opens
watch(isOpen, (open) => {
    if (open) {
        form.reset();
        form.transaction_date = new Date().toISOString().split('T')[0];
        recentDescriptions.value = [];
        fetchRecentDescriptions(undefined, 'expense');
    }
});

// Quick amount presets
const amountPresets = [100, 500, 1000, 2000, 5000, 10000];

const setAmount = (amount: number) => {
    form.amount = amount.toString();
};

const addAmount = (amount: number) => {
    const current = parseFloat(form.amount) || 0;
    form.amount = (current + amount).toString();
};

// Quick description select
const setDescription = (desc: string) => {
    form.description = desc;
};

// Quick category select
const selectCategory = (categoryId: number) => {
    form.category_id = categoryId.toString();
};

// Submit form
const submit = () => {
    form.post('/finance', {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            isOpen.value = false;
            form.reset();
        },
    });
};

// Submit and add another
const submitAndAddAnother = () => {
    form.post('/finance', {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            form.reset();
            form.transaction_date = new Date().toISOString().split('T')[0];
            setTimeout(() => {
                amountInputRef.value?.focus();
            }, 100);
        },
    });
};

// Keyboard shortcuts
const handleKeydown = (e: KeyboardEvent) => {
    if (!isOpen.value) return;

    // Ctrl+Enter to submit and add another
    if (e.ctrlKey && e.key === 'Enter') {
        e.preventDefault();
        if (form.category_id && form.amount && form.description) {
            submitAndAddAnother();
        }
    }
    // Ctrl+S to submit and close
    else if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        if (form.category_id && form.amount && form.description) {
            submit();
        }
    }
    // Escape to close
    else if (e.key === 'Escape') {
        isOpen.value = false;
    }
    // Switch type with Tab + Shift
    else if (e.key === 'Tab' && e.shiftKey && document.activeElement === amountInputRef.value) {
        e.preventDefault();
        form.type = form.type === 'income' ? 'expense' : 'income';
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

// Format currency display
const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('th-TH').format(amount);
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[600px] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <span>⚡</span>
                    <span>บันทึกรายการด่วน</span>
                </DialogTitle>
                <DialogDescription>
                    กด <kbd class="px-1.5 py-0.5 text-xs bg-gray-100 rounded">Ctrl+Enter</kbd> เพื่อบันทึกและเพิ่มรายการต่อ |
                    <kbd class="px-1.5 py-0.5 text-xs bg-gray-100 rounded">Ctrl+S</kbd> เพื่อบันทึกและปิด
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Transaction Type Toggle -->
                <div class="flex rounded-lg overflow-hidden border">
                    <button
                        type="button"
                        @click="form.type = 'income'"
                        :class="[
                            'flex-1 py-3 text-center font-medium transition-all',
                            form.type === 'income'
                                ? 'bg-green-500 text-white'
                                : 'bg-gray-50 text-gray-600 hover:bg-gray-100',
                        ]"
                    >
                        <span class="mr-2">📈</span>รายรับ
                    </button>
                    <button
                        type="button"
                        @click="form.type = 'expense'"
                        :class="[
                            'flex-1 py-3 text-center font-medium transition-all',
                            form.type === 'expense'
                                ? 'bg-red-500 text-white'
                                : 'bg-gray-50 text-gray-600 hover:bg-gray-100',
                        ]"
                    >
                        <span class="mr-2">📉</span>รายจ่าย
                    </button>
                </div>

                <!-- Quick Category Selection -->
                <div>
                    <Label class="text-sm font-medium mb-2 block">หมวดหมู่</Label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="category in filteredCategories"
                            :key="category.id"
                            type="button"
                            @click="selectCategory(category.id)"
                            :class="[
                                'px-3 py-2 text-sm rounded-lg border transition-all',
                                form.category_id === category.id.toString()
                                    ? 'ring-2 ring-offset-1'
                                    : 'hover:bg-gray-50',
                            ]"
                            :style="{
                                borderColor: category.color,
                                backgroundColor: form.category_id === category.id.toString() ? category.color + '20' : 'transparent',
                                color: category.color,
                            }"
                        >
                            {{ category.name }}
                        </button>
                    </div>
                    <p v-if="form.errors.category_id" class="text-red-500 text-xs mt-1">
                        {{ form.errors.category_id }}
                    </p>
                </div>

                <!-- Amount with Quick Presets -->
                <div>
                    <Label class="text-sm font-medium mb-2 block">จำนวนเงิน (บาท)</Label>
                    <Input
                        ref="amountInputRef"
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        placeholder="0.00"
                        class="text-2xl font-bold h-14 text-center"
                        :class="form.type === 'income' ? 'text-green-600' : 'text-red-600'"
                        required
                    />
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        <button
                            v-for="preset in amountPresets"
                            :key="preset"
                            type="button"
                            @click="setAmount(preset)"
                            class="px-2.5 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded transition-colors"
                        >
                            {{ formatCurrency(preset) }}
                        </button>
                        <span class="text-gray-400 mx-1">|</span>
                        <button
                            v-for="preset in [100, 500, 1000]"
                            :key="'add-' + preset"
                            type="button"
                            @click="addAmount(preset)"
                            class="px-2.5 py-1 text-xs bg-blue-100 hover:bg-blue-200 text-blue-700 rounded transition-colors"
                        >
                            +{{ formatCurrency(preset) }}
                        </button>
                    </div>
                    <p v-if="form.errors.amount" class="text-red-500 text-xs mt-1">
                        {{ form.errors.amount }}
                    </p>
                </div>

                <!-- Description with Presets -->
                <div>
                    <Label class="text-sm font-medium mb-2 block">
                        รายละเอียด
                        <span v-if="currentCategory" class="text-gray-400 font-normal ml-1">
                            (หมวด: {{ currentCategory.name }})
                        </span>
                    </Label>
                    <Input
                        v-model="form.description"
                        type="text"
                        placeholder="เช่น ค่าอาหาร, รายได้จากการขาย"
                        required
                    />
                    
                    <!-- Loading indicator -->
                    <div v-if="isLoadingDescriptions" class="mt-2 text-xs text-gray-400">
                        กำลังโหลดรายการที่เคยใช้...
                    </div>
                    
                    <!-- History descriptions (from API) -->
                    <div v-else class="mt-2">
                        <div v-if="recentDescriptions.length > 0" class="mb-2">
                            <span class="text-xs text-gray-500 mb-1 block">📝 เคยใช้บ่อย:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="item in recentDescriptions"
                                    :key="item.description"
                                    type="button"
                                    @click="setDescription(item.description)"
                                    :class="[
                                        'px-2.5 py-1 text-xs rounded transition-colors flex items-center gap-1',
                                        form.description === item.description
                                            ? 'bg-green-500 text-white'
                                            : 'bg-green-50 hover:bg-green-100 text-green-700 border border-green-200',
                                    ]"
                                    :title="`ใช้ ${item.usage_count} ครั้ง`"
                                >
                                    {{ item.description }}
                                    <span v-if="item.usage_count > 1" class="text-[10px] opacity-70">({{ item.usage_count }})</span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Default preset descriptions -->
                        <div>
                            <span class="text-xs text-gray-500 mb-1 block">💡 รายการแนะนำ:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="desc in defaultPresetDescriptions[form.type]"
                                    :key="desc"
                                    type="button"
                                    @click="setDescription(desc)"
                                    :class="[
                                        'px-2.5 py-1 text-xs rounded transition-colors',
                                        form.description === desc
                                            ? 'bg-blue-500 text-white'
                                            : 'bg-gray-100 hover:bg-gray-200 text-gray-700',
                                    ]"
                                >
                                    {{ desc }}
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <p v-if="form.errors.description" class="text-red-500 text-xs mt-1">
                        {{ form.errors.description }}
                    </p>
                </div>

                <!-- Payment Method Quick Select -->
                <div>
                    <Label class="text-sm font-medium mb-2 block">วิธีชำระเงิน</Label>
                    <div class="flex gap-2">
                        <button
                            v-for="method in paymentMethods"
                            :key="method.value"
                            type="button"
                            @click="form.payment_method = method.value"
                            :class="[
                                'flex-1 py-2 px-3 text-sm rounded-lg border transition-all flex items-center justify-center gap-1.5',
                                form.payment_method === method.value
                                    ? 'bg-blue-50 border-blue-500 text-blue-700 ring-1 ring-blue-500'
                                    : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100',
                            ]"
                        >
                            <span>{{ method.icon }}</span>
                            <span class="hidden sm:inline">{{ method.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Date -->
                <div class="flex gap-4">
                    <div class="flex-1">
                        <Label class="text-sm font-medium mb-2 block">วันที่</Label>
                        <Input v-model="form.transaction_date" type="date" required />
                    </div>
                    <div class="flex items-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="form.transaction_date = new Date().toISOString().split('T')[0]"
                        >
                            วันนี้
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="
                                form.transaction_date = new Date(Date.now() - 86400000).toISOString().split('T')[0]
                            "
                        >
                            เมื่อวาน
                        </Button>
                    </div>
                </div>

                <!-- Optional Fields (Collapsible) -->
                <details class="group">
                    <summary class="cursor-pointer text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                        <span class="group-open:rotate-90 transition-transform">▶</span>
                        ข้อมูลเพิ่มเติม (ไม่บังคับ)
                    </summary>
                    <div class="mt-3 space-y-3 pl-4 border-l-2 border-gray-200">
                        <div>
                            <Label class="text-sm font-medium mb-1 block">เลขที่อ้างอิง</Label>
                            <Input v-model="form.reference_number" type="text" placeholder="เช่น เลขที่ใบเสร็จ" />
                        </div>
                        <div>
                            <Label class="text-sm font-medium mb-1 block">หมายเหตุ</Label>
                            <Textarea v-model="form.notes" :rows="2" placeholder="รายละเอียดเพิ่มเติม" />
                        </div>
                    </div>
                </details>
            </form>

            <DialogFooter class="flex-col sm:flex-row gap-2 mt-4">
                <Button type="button" variant="outline" @click="isOpen = false" class="w-full sm:w-auto">
                    ยกเลิก
                </Button>
                <Button
                    type="button"
                    variant="secondary"
                    @click="submitAndAddAnother"
                    :disabled="form.processing || !form.category_id || !form.amount || !form.description"
                    class="w-full sm:w-auto"
                >
                    <span v-if="form.processing">กำลังบันทึก...</span>
                    <span v-else>บันทึก & เพิ่มต่อ</span>
                </Button>
                <Button
                    type="button"
                    @click="submit"
                    :disabled="form.processing || !form.category_id || !form.amount || !form.description"
                    :class="[
                        'w-full sm:w-auto',
                        form.type === 'income' ? 'bg-green-500 hover:bg-green-600' : 'bg-red-500 hover:bg-red-600',
                    ]"
                >
                    <span v-if="form.processing">กำลังบันทึก...</span>
                    <span v-else>บันทึก</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
