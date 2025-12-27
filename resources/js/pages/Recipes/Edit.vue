<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm, Link, router } from '@inertiajs/vue3';

interface Recipe {
    id: number;
    name: string;
    description: string;
    image_url: string | null;
    yield_quantity: number;
    yield_unit: string;
    total_cost: number;
    selling_price: number;
    labor_cost: number;
    overhead_cost: number;
    packaging_cost: number;
    ingredients: Array<{
        id: number;
        name: string;
        pivot: {
            quantity: number;
            unit: string;
        };
    }>;
    steps: Array<{
        id: number;
        step_number: number;
        instruction: string;
        image_url: string | null;
    }>;
}

interface StepFormItem {
    step_number: number;
    instruction: string;
    image: File | null;
    image_preview: string | null;
    existing_image: string | null;
    remove_image: boolean;
}

const props = defineProps<{
    recipe: Recipe;
    ingredients: Array<{
        id: number;
        name: string;
        purchase_unit: string;
        cost_per_unit: number;
    }>;
    units: Array<{
        id: number;
        name: string;
        abbreviation: string;
    }>;
}>();

const breadcrumbs = [
    {
        title: 'สูตรอาหาร (SOP)',
        href: '/recipes',
    },
    {
        title: props.recipe.name,
        href: `/recipes/${props.recipe.id}`,
    },
    {
        title: 'แก้ไข',
        href: `/recipes/${props.recipe.id}/edit`,
    },
];

// Transform recipe ingredients for form
const initialIngredients = props.recipe.ingredients.map(ing => ({
    id: ing.id,
    quantity: ing.pivot.quantity,
    unit: ing.pivot.unit,
}));

// Transform recipe steps for form
const initialSteps: StepFormItem[] = props.recipe.steps.map(step => ({
    step_number: step.step_number,
    instruction: step.instruction,
    image: null,
    image_preview: null,
    existing_image: step.image_url,
    remove_image: false,
}));

const form = useForm({
    name: props.recipe.name,
    description: props.recipe.description || '',
    image: null as File | null,
    image_preview: null as string | null,
    existing_image: props.recipe.image_url,
    remove_image: false,
    yield_quantity: props.recipe.yield_quantity,
    yield_unit: props.recipe.yield_unit,
    selling_price: props.recipe.selling_price?.toString() || '',
    labor_cost: props.recipe.labor_cost?.toString() || '',
    overhead_cost: props.recipe.overhead_cost?.toString() || '',
    packaging_cost: props.recipe.packaging_cost?.toString() || '',
    ingredients: initialIngredients as Array<{ id: number; quantity: number; unit: string }>,
    steps: initialSteps as Array<StepFormItem>,
});

const handleRecipeImage = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.image = file;
        form.image_preview = URL.createObjectURL(file);
        form.remove_image = false;
    }
};

const removeRecipeImage = () => {
    form.image = null;
    form.image_preview = null;
    form.remove_image = true;
};

const addIngredient = () => {
    form.ingredients.push({ id: 0, quantity: 0, unit: '' });
};

const removeIngredient = (index: number) => {
    form.ingredients.splice(index, 1);
};

const addStep = () => {
    form.steps.push({ step_number: form.steps.length + 1, instruction: '', image: null, image_preview: null, existing_image: null, remove_image: false });
};

const removeStep = (index: number) => {
    form.steps.splice(index, 1);
    // Re-index steps
    form.steps.forEach((step, i) => {
        step.step_number = i + 1;
    });
};

const handleStepImage = (event: Event, index: number) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        form.steps[index].image = file;
        form.steps[index].image_preview = URL.createObjectURL(file);
        form.steps[index].remove_image = false;
    }
};

const removeStepImage = (index: number) => {
    form.steps[index].image = null;
    form.steps[index].image_preview = null;
    form.steps[index].remove_image = true;
};

const submit = () => {
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('name', form.name);
    formData.append('description', form.description);
    formData.append('yield_quantity', form.yield_quantity.toString());
    formData.append('yield_unit', form.yield_unit);
    formData.append('selling_price', form.selling_price);
    formData.append('labor_cost', form.labor_cost);
    formData.append('overhead_cost', form.overhead_cost);
    formData.append('packaging_cost', form.packaging_cost);
    formData.append('remove_image', form.remove_image ? '1' : '0');
    
    if (form.image) {
        formData.append('image', form.image);
    }
    
    form.ingredients.forEach((item, i) => {
        formData.append(`ingredients[${i}][id]`, item.id.toString());
        formData.append(`ingredients[${i}][quantity]`, item.quantity.toString());
        formData.append(`ingredients[${i}][unit]`, item.unit);
    });
    
    form.steps.forEach((step, i) => {
        formData.append(`steps[${i}][step_number]`, step.step_number.toString());
        formData.append(`steps[${i}][instruction]`, step.instruction);
        formData.append(`steps[${i}][remove_image]`, step.remove_image ? '1' : '0');
        if (step.image) {
            formData.append(`steps[${i}][image]`, step.image);
        }
    });
    
    router.post(`/recipes/${props.recipe.id}`, formData);
};

const deleteRecipe = () => {
    if (confirm('คุณต้องการลบสูตรอาหารนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        router.delete(`/recipes/${props.recipe.id}`);
    }
};
</script>

<template>
    <Head :title="`แก้ไข ${recipe.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="mb-6 px-6 flex justify-between items-center">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        แก้ไขสูตรอาหาร: {{ recipe.name }}
                    </h2>
                    <button 
                        @click="deleteRecipe" 
                        class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                    >
                        ลบสูตร
                    </button>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <!-- Basic Info -->
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">ข้อมูลพื้นฐาน</h3>
                                
                                <!-- Recipe Image (Required) -->
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">
                                        รูปภาพเมนู <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex items-start gap-4">
                                        <div v-if="form.image_preview || (form.existing_image && !form.remove_image)" class="relative">
                                            <img :src="form.image_preview || form.existing_image" class="w-40 h-40 object-cover rounded-lg border-2 border-gray-300" />
                                            <button type="button" @click="removeRecipeImage" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 text-sm flex items-center justify-center hover:bg-red-600">×</button>
                                        </div>
                                        <div v-else class="flex flex-col items-center justify-center w-40 h-40 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100 cursor-pointer">
                                            <label class="cursor-pointer flex flex-col items-center justify-center w-full h-full">
                                                <span class="text-4xl text-gray-400">📷</span>
                                                <span class="text-sm text-gray-500 mt-2">เลือกรูปเมนู</span>
                                                <span class="text-xs text-red-500 mt-1">จำเป็น</span>
                                                <input type="file" @change="handleRecipeImage" accept="image/*" class="hidden" />
                                            </label>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <p>• รูปภาพที่แสดงผลงานสำเร็จของเมนู</p>
                                            <p>• แนะนำขนาด 800x800 px</p>
                                            <p>• ขนาดไฟล์ไม่เกิน 2MB</p>
                                        </div>
                                    </div>
                                    <div v-if="form.errors.image" class="text-red-500 text-xs italic mt-1">{{ form.errors.image }}</div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">ชื่อสูตร</label>
                                        <input v-model="form.name" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                        <div v-if="form.errors.name" class="text-red-500 text-xs italic">{{ form.errors.name }}</div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">ราคาขาย</label>
                                        <input v-model="form.selling_price" type="number" step="0.01" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">คำอธิบาย</label>
                                    <textarea v-model="form.description" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">ปริมาณผลผลิต</label>
                                        <input v-model="form.yield_quantity" type="number" step="0.1" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">หน่วยผลผลิต</label>
                                        <select v-model="form.yield_unit" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>
                                            <option value="">เลือกหน่วย</option>
                                            <option v-for="unit in units" :key="unit.id" :value="unit.abbreviation">
                                                {{ unit.name }} ({{ unit.abbreviation }})
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Costs -->
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">ต้นทุนเพิ่มเติม</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">ค่าแรงงาน (บาท)</label>
                                        <input v-model="form.labor_cost" type="number" step="0.01" min="0" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="0.00">
                                        <p class="text-xs text-gray-500 mt-1">ค่าแรงในการผลิตสูตรนี้</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">ค่าใช้จ่ายการผลิต (บาท)</label>
                                        <input v-model="form.overhead_cost" type="number" step="0.01" min="0" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="0.00">
                                        <p class="text-xs text-gray-500 mt-1">ค่าไฟฟ้า แก๊ส เครื่องจักร</p>
                                    </div>
                                    <div class="mb-4">
                                        <label class="block text-gray-700 text-sm font-bold mb-2">ค่าบรรจุภัณฑ์ (บาท)</label>
                                        <input v-model="form.packaging_cost" type="number" step="0.01" min="0" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="0.00">
                                        <p class="text-xs text-gray-500 mt-1">ค่ากล่อง ถุง ฉลาก</p>
                                    </div>
                                </div>
                            </div>


                            <!-- Ingredients -->
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">วัตถุดิบ</h3>
                                    <button type="button" @click="addIngredient" class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-sm">เพิ่มวัตถุดิบ</button>
                                </div>
                                <div v-for="(item, index) in form.ingredients" :key="index" class="flex gap-4 mb-2 items-end">
                                    <div class="flex-1">
                                        <label class="block text-gray-700 text-xs font-bold mb-1">วัตถุดิบ</label>
                                        <select v-model="item.id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                            <option value="0">เลือกวัตถุดิบ</option>
                                            <option v-for="ing in ingredients" :key="ing.id" :value="ing.id">{{ ing.name }} ({{ ing.purchase_unit }})</option>
                                        </select>
                                    </div>
                                    <div class="w-32">
                                        <label class="block text-gray-700 text-xs font-bold mb-1">จำนวน</label>
                                        <input v-model="item.quantity" type="number" step="0.0001" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    </div>
                                    <div class="w-40">
                                        <label class="block text-gray-700 text-xs font-bold mb-1">หน่วย</label>
                                        <select v-model="item.unit" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                            <option value="">เลือกหน่วย</option>
                                            <option v-for="unit in units" :key="unit.id" :value="unit.abbreviation">
                                                {{ unit.abbreviation }}
                                            </option>
                                        </select>
                                    </div>
                                    <button type="button" @click="removeIngredient(index)" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-3 rounded mb-0.5">X</button>
                                </div>
                                <p v-if="form.ingredients.length === 0" class="text-gray-500 text-sm">ยังไม่มีวัตถุดิบ กดปุ่ม "เพิ่มวัตถุดิบ" เพื่อเพิ่ม</p>
                            </div>

                            <!-- Steps -->
                            <div class="mb-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">ขั้นตอนการเตรียม (SOP)</h3>
                                    <button type="button" @click="addStep" class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-sm">เพิ่มขั้นตอน</button>
                                </div>
                                <div v-for="(step, index) in form.steps" :key="index" class="mb-4 p-4 border rounded-lg bg-gray-50">
                                    <div class="flex gap-4 items-start">
                                        <div class="w-12 pt-2 font-bold text-gray-700 text-lg">
                                            {{ index + 1 }}.
                                        </div>
                                        <div class="flex-1 space-y-3">
                                            <textarea v-model="step.instruction" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="2" placeholder="อธิบายขั้นตอน..."></textarea>
                                            
                                            <!-- Image Upload -->
                                            <div class="flex items-center gap-4">
                                                <div v-if="step.image_preview || (step.existing_image && !step.remove_image)" class="relative">
                                                    <img :src="step.image_preview || step.existing_image" class="w-24 h-24 object-cover rounded border" />
                                                    <button type="button" @click="removeStepImage(index)" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center">×</button>
                                                </div>
                                                <div v-else class="flex items-center">
                                                    <label class="cursor-pointer bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-3 rounded text-sm flex items-center gap-2">
                                                        <span>📷</span>
                                                        <span>เพิ่มรูปภาพ (ไม่บังคับ)</span>
                                                        <input type="file" @change="(e) => handleStepImage(e, index)" accept="image/*" class="hidden" />
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" @click="removeStep(index)" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-3 rounded">X</button>
                                    </div>
                                </div>
                                <p v-if="form.steps.length === 0" class="text-gray-500 text-sm">ยังไม่มีขั้นตอน กดปุ่ม "เพิ่มขั้นตอน" เพื่อเพิ่ม</p>
                            </div>

                            <div class="flex items-center justify-between">
                                <Link :href="`/recipes/${recipe.id}`" class="text-gray-600 hover:text-gray-900">
                                    ← ยกเลิก
                                </Link>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" :disabled="form.processing">
                                    {{ form.processing ? 'กำลังบันทึก...' : 'บันทึกการแก้ไข' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
