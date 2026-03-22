<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index, edit, destroy } from '@/routes/recipes';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ChefHat, ArrowLeft, Printer, Pencil, Trash2, Package, Tag, Coins, BarChart3 } from 'lucide-vue-next';

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
        title: 'สูตรอาหาร (SOP)',
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
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 p-3 sm:p-4 md:p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight">{{ recipe.name }}</h1>
                    <p class="text-sm text-muted-foreground">สูตรอาหาร (SOP)</p>
                </div>
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <Button as-child variant="outline" size="sm">
                        <Link :href="index().url">
                            <ArrowLeft class="mr-1.5 h-4 w-4" /> กลับ
                        </Link>
                    </Button>
                    <Button as-child variant="outline" size="sm">
                        <a :href="`/recipes/${recipe.id}/print`" target="_blank">
                            <Printer class="mr-1.5 h-4 w-4" /> พิมพ์
                        </a>
                    </Button>
                    <Button as-child size="sm">
                        <Link :href="edit(recipe.id).url">
                            <Pencil class="mr-1.5 h-4 w-4" /> แก้ไข
                        </Link>
                    </Button>
                    <Button @click="deleteRecipe" variant="destructive" size="sm">
                        <Trash2 class="mr-1.5 h-4 w-4" /> ลบ
                    </Button>
                </div>
            </div>

            <!-- Recipe Image & Info Cards -->
            <div class="flex flex-col md:flex-row gap-4 sm:gap-6">
                <div class="flex-shrink-0">
                    <Card class="overflow-hidden">
                        <div v-if="recipe.image_url" class="w-full md:w-48 h-48">
                            <img :src="recipe.image_url" :alt="recipe.name" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-full md:w-48 h-48 bg-muted flex items-center justify-center">
                            <ChefHat class="h-16 w-16 text-muted-foreground/30" />
                        </div>
                    </Card>
                </div>
                
                <div class="flex-1 grid grid-cols-2 gap-3 sm:gap-4">
                    <Card class="border-blue-200 dark:border-blue-800/50">
                        <CardContent class="p-3 sm:p-4">
                            <div class="flex items-center gap-2 mb-1">
                                <Package class="h-4 w-4 text-blue-500" />
                                <span class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase">ปริมาณผลผลิต</span>
                            </div>
                            <p class="text-xl font-bold">{{ recipe.yield_quantity }} {{ recipe.yield_unit }}</p>
                        </CardContent>
                    </Card>
                    <Card class="border-green-200 dark:border-green-800/50">
                        <CardContent class="p-3 sm:p-4">
                            <div class="flex items-center gap-2 mb-1">
                                <Tag class="h-4 w-4 text-green-500" />
                                <span class="text-xs font-medium text-green-600 dark:text-green-400 uppercase">ราคาขาย</span>
                            </div>
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ recipe.selling_price || '-' }} THB</p>
                            <p v-if="profit" class="text-sm" :class="parseFloat(profit) >= 0 ? 'text-green-600' : 'text-red-600'">
                                กำไร: {{ profit }} THB ({{ profitMargin }}%)
                            </p>
                        </CardContent>
                    </Card>
                    <Card class="border-red-200 dark:border-red-800/50">
                        <CardContent class="p-3 sm:p-4">
                            <div class="flex items-center gap-2 mb-1">
                                <Coins class="h-4 w-4 text-red-500" />
                                <span class="text-xs font-medium text-red-600 dark:text-red-400 uppercase">ต้นทุนรวม</span>
                            </div>
                            <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ Number(recipe.total_cost).toFixed(2) }} THB</p>
                            <p class="text-xs text-muted-foreground">{{ costPerUnit }} THB/หน่วย</p>
                        </CardContent>
                    </Card>
                    <Card class="border-purple-200 dark:border-purple-800/50">
                        <CardContent class="p-3 sm:p-4">
                            <div class="flex items-center gap-2 mb-1">
                                <BarChart3 class="h-4 w-4 text-purple-500" />
                                <span class="text-xs font-medium text-purple-600 dark:text-purple-400 uppercase">โครงสร้างต้นทุน</span>
                            </div>
                            <p class="text-xs">วัตถุดิบ: {{ ingredientCost }} | แรงงาน: {{ Number(recipe.labor_cost || 0).toFixed(2) }}</p>
                            <p class="text-xs">ค่าผลิต: {{ Number(recipe.overhead_cost || 0).toFixed(2) }} | บรรจุภัณฑ์: {{ Number(recipe.packaging_cost || 0).toFixed(2) }}</p>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Cost Breakdown -->
            <Card>
                <CardHeader class="pb-3">
                    <CardTitle>สรุปต้นทุน</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <div class="text-center p-3 rounded-lg bg-muted/50">
                            <p class="text-xs text-muted-foreground uppercase mb-1">วัตถุดิบ</p>
                            <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ ingredientCost }}</p>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-muted/50">
                            <p class="text-xs text-muted-foreground uppercase mb-1">แรงงาน</p>
                            <p class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ Number(recipe.labor_cost || 0).toFixed(2) }}</p>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-muted/50">
                            <p class="text-xs text-muted-foreground uppercase mb-1">ค่าผลิต</p>
                            <p class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ Number(recipe.overhead_cost || 0).toFixed(2) }}</p>
                        </div>
                        <div class="text-center p-3 rounded-lg bg-muted/50">
                            <p class="text-xs text-muted-foreground uppercase mb-1">บรรจุภัณฑ์</p>
                            <p class="text-lg font-bold text-yellow-600 dark:text-yellow-400">{{ Number(recipe.packaging_cost || 0).toFixed(2) }}</p>
                        </div>
                        <div class="text-center p-3 rounded-lg border-l-2 border-primary bg-muted/50">
                            <p class="text-xs text-muted-foreground uppercase mb-1">รวมทั้งหมด</p>
                            <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ Number(recipe.total_cost).toFixed(2) }}</p>
                            <p class="text-xs text-muted-foreground">{{ costPerUnit }} THB/หน่วย</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="recipe.description">
                <CardHeader class="pb-3">
                    <CardTitle>รายละเอียด</CardTitle>
                </CardHeader>
                <CardContent>
                    <p>{{ recipe.description }}</p>
                </CardContent>
            </Card>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <!-- Ingredients List -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle>วัตถุดิบ</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul class="space-y-2">
                            <li v-for="ingredient in recipe.ingredients" :key="ingredient.id" class="flex justify-between items-center p-2.5 hover:bg-muted/50 rounded-lg transition-colors">
                                <span class="font-medium">{{ ingredient.name }}</span>
                                <Badge variant="secondary">{{ ingredient.pivot.quantity }} {{ ingredient.pivot.unit }}</Badge>
                            </li>
                        </ul>
                    </CardContent>
                </Card>

                <!-- Preparation Steps -->
                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle>ขั้นตอนการเตรียม</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="step in recipe.steps" :key="step.id" class="flex gap-4 p-3 bg-muted/50 rounded-lg">
                                <div class="flex-shrink-0 w-10 h-10 bg-primary text-primary-foreground rounded-full flex items-center justify-center font-bold text-sm shadow-sm">
                                    {{ step.step_number }}
                                </div>
                                <div class="flex-1 pt-1">
                                    <p>{{ step.instruction }}</p>
                                    <div v-if="step.image_url" class="mt-3">
                                        <a :href="step.image_url" target="_blank" rel="noopener noreferrer">
                                            <img :src="step.image_url" :alt="`ขั้นตอนที่ ${step.step_number}`" class="w-64 h-48 object-cover rounded-lg border shadow-md hover:scale-[1.02] transition-transform cursor-pointer" />
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
