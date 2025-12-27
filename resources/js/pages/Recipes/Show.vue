<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index, edit, destroy } from '@/routes/recipes';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    recipe: {
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
    };
}>();

const breadcrumbs = [
    {
        title: 'Recipes (SOP)',
        href: '/recipes',
    },
    {
        title: props.recipe.name,
        href: `/recipes/${props.recipe.id}`,
    },
];

// คำนวณต้นทุนวัตถุดิบต่อหน่วย
const costPerUnit = computed(() => {
    if (props.recipe.yield_quantity > 0 && props.recipe.total_cost > 0) {
        return (props.recipe.total_cost / props.recipe.yield_quantity).toFixed(2);
    }
    return '0.00';
});

// คำนวณต้นทุนวัตถุดิบ
 const ingredientCost = computed(() => {
    const total = props.recipe.total_cost || 0;
    const labor = props.recipe.labor_cost || 0;
    const overhead = props.recipe.overhead_cost || 0;
    const packaging = props.recipe.packaging_cost || 0;
    return (total - labor - overhead - packaging).toFixed(2);
});

// คำนวณกำไร
const profit = computed(() => {
    if (props.recipe.selling_price && props.recipe.total_cost && props.recipe.yield_quantity > 0) {
        const profitPerUnit = props.recipe.selling_price - (props.recipe.total_cost / props.recipe.yield_quantity);
        return profitPerUnit.toFixed(2);
    }
    return null;
});

// คำนวณ margin %
const profitMargin = computed(() => {
    if (props.recipe.selling_price && props.recipe.total_cost && props.recipe.yield_quantity > 0) {
        const costPerItem = props.recipe.total_cost / props.recipe.yield_quantity;
        const margin = ((props.recipe.selling_price - costPerItem) / props.recipe.selling_price) * 100;
        return margin.toFixed(1);
    }
    return null;
});

const deleteRecipe = () => {
    if (confirm('คุณต้องการลบสูตรอาหารนี้หรือไม่? การกระทำนี้ไม่สามารถย้อนกลับได้')) {
        router.delete(destroy(props.recipe.id).url);
    }
};
</script>

<template>
    <Head :title="recipe.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-6 px-6">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ recipe.name }} (SOP)
                    </h2>
                    <div class="flex gap-2">
                        <Link :href="index().url" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            ← กลับ
                        </Link>
                        <a :href="`/recipes/${recipe.id}/print`" target="_blank" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            🖨️ พิมพ์
                        </a>
                        <Link :href="edit(recipe.id).url" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            แก้ไข
                        </Link>
                        <button @click="deleteRecipe" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                            ลบ
                        </button>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <!-- Recipe Image & Header Info -->
                        <div class="flex flex-col md:flex-row gap-6 mb-8">
                            <!-- Recipe Main Image -->
                            <div class="flex-shrink-0">
                                <div v-if="recipe.image_url" class="w-48 h-48 rounded-xl overflow-hidden shadow-lg border-4 border-gray-100">
                                    <img :src="recipe.image_url" :alt="recipe.name" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-48 h-48 rounded-xl bg-gray-100 flex items-center justify-center border-4 border-gray-200">
                                    <span class="text-6xl">🍽️</span>
                                </div>
                            </div>
                            
                            <!-- Info Cards -->
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <h3 class="text-sm font-bold text-blue-600 uppercase mb-1">📦 Yield</h3>
                                    <p class="text-xl font-semibold">{{ recipe.yield_quantity }} {{ recipe.yield_unit }}</p>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                    <h3 class="text-sm font-bold text-green-600 uppercase mb-1">🏷️ Selling Price</h3>
                                    <p class="text-xl font-semibold text-green-600">{{ recipe.selling_price || '-' }} THB</p>
                                    <p v-if="profit" class="text-sm" :class="parseFloat(profit) >= 0 ? 'text-green-600' : 'text-red-600'">
                                        กำไร: {{ profit }} THB ({{ profitMargin }}%)
                                    </p>
                                </div>
                                <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                                    <h3 class="text-sm font-bold text-red-600 uppercase mb-1">💰 Total Cost</h3>
                                    <p class="text-xl font-semibold text-red-600">{{ Number(recipe.total_cost).toFixed(2) }} THB</p>
                                    <p class="text-sm text-gray-500">{{ costPerUnit }} THB/หน่วย</p>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                    <h3 class="text-sm font-bold text-purple-600 uppercase mb-1">📊 Cost Breakdown</h3>
                                    <p class="text-sm">วัตถุดิบ: {{ ingredientCost }} | แรงงาน: {{ Number(recipe.labor_cost || 0).toFixed(2) }}</p>
                                    <p class="text-sm">ค่าผลิต: {{ Number(recipe.overhead_cost || 0).toFixed(2) }} | บรรจุภัณฑ์: {{ Number(recipe.packaging_cost || 0).toFixed(2) }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Cost Breakdown -->
                        <div class="mb-8 bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <h3 class="text-lg font-bold text-gray-900 mb-3">สรุปต้นทุน</h3>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase mb-1">วัตถุดิบ</p>
                                    <p class="text-lg font-bold text-blue-600">{{ ingredientCost }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase mb-1">แรงงาน</p>
                                    <p class="text-lg font-bold text-purple-600">{{ Number(recipe.labor_cost || 0).toFixed(2) }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase mb-1">ค่าผลิต</p>
                                    <p class="text-lg font-bold text-orange-600">{{ Number(recipe.overhead_cost || 0).toFixed(2) }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase mb-1">บรรจุภัณฑ์</p>
                                    <p class="text-lg font-bold text-yellow-600">{{ Number(recipe.packaging_cost || 0).toFixed(2) }}</p>
                                </div>
                                <div class="text-center border-l-2 border-blue-300">
                                    <p class="text-xs text-gray-500 uppercase mb-1">รวมทั้งหมด</p>
                                    <p class="text-xl font-bold text-red-600">{{ Number(recipe.total_cost).toFixed(2) }}</p>
                                    <p class="text-xs text-gray-500">{{ costPerUnit }} THB/หน่วย</p>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8" v-if="recipe.description">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Description</h3>
                            <p class="text-gray-700">{{ recipe.description }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Ingredients List -->
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Ingredients</h3>
                                <ul class="space-y-2">
                                    <li v-for="ingredient in recipe.ingredients" :key="ingredient.id" class="flex justify-between items-center p-2 hover:bg-gray-50 rounded">
                                        <span class="font-medium">{{ ingredient.name }}</span>
                                        <span class="text-gray-600">{{ ingredient.pivot.quantity }} {{ ingredient.pivot.unit }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Preparation Steps -->
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Preparation Steps</h3>
                                <div class="space-y-4">
                                    <div v-for="step in recipe.steps" :key="step.id" class="flex gap-4 p-3 bg-gray-50 rounded-lg">
                                        <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-full flex items-center justify-center font-bold shadow-md">
                                            {{ step.step_number }}
                                        </div>
                                        <div class="flex-1 pt-1">
                                            <p class="text-gray-800">{{ step.instruction }}</p>
                                            <!-- Step Image -->
                                            <div v-if="step.image_url" class="mt-3">
                                                <a :href="step.image_url" target="_blank">
                                                    <img :src="step.image_url" :alt="`ขั้นตอนที่ ${step.step_number}`" class="w-64 h-48 object-cover rounded-lg border-2 border-gray-200 shadow-md hover:scale-105 transition-transform cursor-pointer" />
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
