<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { create, show, edit, destroy } from '@/routes/recipes';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ChefHat, Package, FileText, Pencil, Trash2, Plus } from 'lucide-vue-next';

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
    <Head title="สูตรอาหาร (SOP)" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 sm:gap-6 p-3 sm:p-4 md:p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight">สูตรอาหาร (SOP)</h1>
                    <p class="text-sm text-muted-foreground">จัดการสูตรอาหารและขั้นตอนการผลิต</p>
                </div>
                <Button as-child size="sm">
                    <Link :href="create().url">
                        <Plus class="mr-1.5 h-4 w-4" />
                        สร้างสูตร
                    </Link>
                </Button>
            </div>

            <!-- Card Grid View -->
            <div v-if="recipes.length > 0" class="grid grid-cols-1 xs:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4 lg:gap-5">
                <Card v-for="recipe in recipes" :key="recipe.id" class="overflow-hidden hover:shadow-lg transition-all duration-300 group">
                    <!-- Recipe Image -->
                    <Link :href="show(recipe.id).url" class="block">
                        <div class="relative h-48 bg-muted">
                            <img v-if="recipe.image_url" :src="recipe.image_url" :alt="recipe.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                            <div v-else class="w-full h-full flex items-center justify-center">
                                <ChefHat class="h-16 w-16 text-muted-foreground/30" />
                            </div>
                            <!-- Price Badge -->
                            <Badge v-if="recipe.selling_price" class="absolute top-2.5 right-2.5 bg-green-600 hover:bg-green-600 text-white shadow-md">
                                ฿{{ Number(recipe.selling_price).toLocaleString() }}
                            </Badge>
                        </div>
                    </Link>
                    
                    <!-- Recipe Info -->
                    <CardContent class="p-4">
                        <Link :href="show(recipe.id).url" class="block">
                            <h3 class="font-semibold text-base mb-2 hover:text-primary truncate transition-colors">{{ recipe.name }}</h3>
                        </Link>
                        
                        <div class="flex justify-between items-center text-sm text-muted-foreground mb-3">
                            <span class="flex items-center gap-1.5">
                                <Package class="h-3.5 w-3.5" />
                                <span>{{ recipe.yield_quantity }} {{ recipe.yield_unit }}</span>
                            </span>
                            <span class="text-red-600 dark:text-red-400 font-medium text-xs">
                                ต้นทุน ฿{{ Number(recipe.total_cost).toFixed(2) }}
                            </span>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex justify-between items-center pt-3 border-t">
                            <Button as-child variant="ghost" size="sm" class="h-8 px-2 text-xs">
                                <Link :href="show(recipe.id).url">
                                    <FileText class="mr-1 h-3.5 w-3.5" /> ดู SOP
                                </Link>
                            </Button>
                            <div class="flex gap-0.5">
                                <Button as-child variant="ghost" size="icon" class="h-8 w-8">
                                    <Link :href="edit(recipe.id).url" title="แก้ไข">
                                        <Pencil class="h-3.5 w-3.5" />
                                    </Link>
                                </Button>
                                <Button @click="deleteRecipe(recipe.id)" variant="ghost" size="icon" class="h-8 w-8 text-destructive hover:text-destructive" title="ลบ">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <Card v-else>
                <CardContent class="p-6 sm:p-12 text-center">
                    <ChefHat class="h-16 w-16 mx-auto text-muted-foreground/40 mb-4" />
                    <h3 class="text-xl font-semibold mb-2">ยังไม่มีสูตรอาหาร</h3>
                    <p class="text-muted-foreground mb-4">เริ่มต้นสร้างสูตรอาหาร (SOP) ของคุณ</p>
                    <Button as-child>
                        <Link :href="create().url">
                            <Plus class="mr-1.5 h-4 w-4" />
                            สร้างสูตรแรก
                        </Link>
                    </Button>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
