<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create, show, edit, destroy } from '@/routes/recipes';
import { Head, Link, router } from '@inertiajs/vue3';

const breadcrumbs = [
    {
        title: 'สูตรอาหาร (SOP)',
        href: '/recipes',
    },
];

defineProps<{
    recipes: Array<{
        id: number;
        name: string;
        image_url: string | null;
        yield_quantity: number;
        yield_unit: string;
        total_cost: number;
        selling_price: number;
    }>;
}>();

const deleteRecipe = (id: number) => {
    if (confirm('คุณต้องการลบสูตรอาหารนี้หรือไม่?')) {
        router.delete(destroy(id).url);
    }
};
</script>

<template>
    <Head title="Recipes (SOP)" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-6 px-6">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        สูตรอาหาร (SOP)
                    </h2>
                    <Link :href="create().url" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        สร้างสูตร
                    </Link>
                </div>

                <!-- Card Grid View -->
                <div v-if="recipes.length > 0" class="px-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div v-for="recipe in recipes" :key="recipe.id" class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                        <!-- Recipe Image -->
                        <Link :href="show(recipe.id).url" class="block">
                            <div class="relative h-48 bg-gray-100">
                                <img v-if="recipe.image_url" :src="recipe.image_url" :alt="recipe.name" class="w-full h-full object-cover" />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <span class="text-6xl text-gray-300">🍽️</span>
                                </div>
                                <!-- Price Badge -->
                                <div v-if="recipe.selling_price" class="absolute top-2 right-2 bg-green-500 text-white px-2 py-1 rounded-full text-sm font-bold">
                                    ฿{{ Number(recipe.selling_price).toLocaleString() }}
                                </div>
                            </div>
                        </Link>
                        
                        <!-- Recipe Info -->
                        <div class="p-4">
                            <Link :href="show(recipe.id).url" class="block">
                                <h3 class="font-bold text-lg text-gray-800 mb-2 hover:text-blue-600 truncate">{{ recipe.name }}</h3>
                            </Link>
                            
                            <div class="flex justify-between items-center text-sm text-gray-600 mb-3">
                                <span class="flex items-center gap-1">
                                    <span>📦</span>
                                    <span>{{ recipe.yield_quantity }} {{ recipe.yield_unit }}</span>
                                </span>
                                <span class="text-red-600 font-medium">
                                    ต้นทุน: ฿{{ Number(recipe.total_cost).toFixed(2) }}
                                </span>
                            </div>
                            
                            <!-- Actions -->
                            <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                                <Link :href="show(recipe.id).url" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center gap-1">
                                    <span>📋</span> ดู SOP
                                </Link>
                                <div class="flex gap-2">
                                    <Link :href="edit(recipe.id).url" class="text-blue-600 hover:text-blue-800 text-sm">
                                        ✏️
                                    </Link>
                                    <button @click="deleteRecipe(recipe.id)" class="text-red-600 hover:text-red-800 text-sm">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg mx-6">
                    <div class="p-12 text-center">
                        <span class="text-6xl mb-4 block">📝</span>
                        <h3 class="text-xl font-semibold text-gray-700 mb-2">ยังไม่มีสูตรอาหาร</h3>
                        <p class="text-gray-500 mb-4">เริ่มต้นสร้างสูตรอาหาร (SOP) ของคุณ</p>
                        <Link :href="create().url" class="inline-block bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                            สร้างสูตรแรก
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
