<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { 
    Activity,
    Search,
    User,
    Calendar,
    Filter,
    PlusCircle,
    Edit,
    Trash,
    Eye,
    LogIn,
    LogOut,
    PackagePlus,
    PackageMinus,
    Sliders,
    ExternalLink,
    ChevronLeft,
    ChevronRight,
} from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';

interface User {
    id: number;
    name: string;
}

interface ActivityLog {
    id: number;
    user_id: number | null;
    action: string;
    model_type: string | null;
    model_id: number | null;
    description: string;
    old_values: Record<string, any> | null;
    new_values: Record<string, any> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    user: User | null;
    action_label: string;
    model_type_label: string;
    action_icon: string;
    action_color: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedLogs {
    data: ActivityLog[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLink[];
}

interface ModelType {
    value: string;
    label: string;
}

interface Props {
    logs: PaginatedLogs;
    filters: {
        user_id?: string;
        action?: string;
        model_type?: string;
        date_from?: string;
        date_to?: string;
        search?: string;
    };
    actions: string[];
    modelTypes: ModelType[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'แดชบอร์ด', href: '/dashboard' },
    { title: 'ประวัติกิจกรรม', href: '/activity-logs' },
];

const search = ref(props.filters.search || '');
const selectedAction = ref(props.filters.action || '');
const selectedModelType = ref(props.filters.model_type || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');

const applyFilters = () => {
    router.get('/activity-logs', {
        search: search.value || undefined,
        action: selectedAction.value || undefined,
        model_type: selectedModelType.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const debouncedSearch = useDebounceFn(applyFilters, 500);

watch(search, () => {
    debouncedSearch();
});

const clearFilters = () => {
    search.value = '';
    selectedAction.value = '';
    selectedModelType.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    router.get('/activity-logs');
};

const getActionIcon = (action: string) => {
    switch (action) {
        case 'created': return PlusCircle;
        case 'updated': return Edit;
        case 'deleted': return Trash;
        case 'viewed': return Eye;
        case 'logged_in': return LogIn;
        case 'logged_out': return LogOut;
        case 'stock_in': return PackagePlus;
        case 'stock_out': return PackageMinus;
        case 'stock_adjustment': return Sliders;
        default: return Activity;
    }
};

const getActionColor = (action: string) => {
    switch (action) {
        case 'created': return 'text-green-600 bg-green-100 dark:bg-green-900/30';
        case 'updated': return 'text-blue-600 bg-blue-100 dark:bg-blue-900/30';
        case 'deleted': return 'text-red-600 bg-red-100 dark:bg-red-900/30';
        case 'viewed': return 'text-gray-600 bg-gray-100 dark:bg-gray-900/30';
        case 'logged_in': return 'text-purple-600 bg-purple-100 dark:bg-purple-900/30';
        case 'logged_out': return 'text-orange-600 bg-orange-100 dark:bg-orange-900/30';
        case 'stock_in': return 'text-emerald-600 bg-emerald-100 dark:bg-emerald-900/30';
        case 'stock_out': return 'text-amber-600 bg-amber-100 dark:bg-amber-900/30';
        case 'stock_adjustment': return 'text-indigo-600 bg-indigo-100 dark:bg-indigo-900/30';
        default: return 'text-gray-600 bg-gray-100 dark:bg-gray-900/30';
    }
};

const getActionLabel = (action: string) => {
    switch (action) {
        case 'created': return 'สร้าง';
        case 'updated': return 'แก้ไข';
        case 'deleted': return 'ลบ';
        case 'viewed': return 'ดู';
        case 'logged_in': return 'เข้าระบบ';
        case 'logged_out': return 'ออกระบบ';
        case 'stock_in': return 'รับสต็อค';
        case 'stock_out': return 'เบิกสต็อค';
        case 'stock_adjustment': return 'ปรับสต็อค';
        default: return action;
    }
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('th-TH', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getModelLink = (log: ActivityLog) => {
    if (!log.model_type || !log.model_id || log.action === 'deleted') return null;
    
    switch (log.model_type) {
        case 'App\\Models\\Ingredient': return `/ingredients/${log.model_id}`;
        case 'App\\Models\\Recipe': return `/recipes/${log.model_id}`;
        case 'App\\Models\\FinancialTransaction': return `/finance/${log.model_id}/edit`;
        default: return null;
    }
};

const hasActiveFilters = computed(() => {
    return search.value || selectedAction.value || selectedModelType.value || dateFrom.value || dateTo.value;
});
</script>

<template>
    <Head title="ประวัติกิจกรรม" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">ประวัติกิจกรรม</h1>
                    <p class="text-muted-foreground">บันทึกการกระทำทั้งหมดในระบบ</p>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader class="pb-3">
                    <div class="flex items-center gap-2">
                        <Filter class="h-4 w-4" />
                        <CardTitle class="text-base">ตัวกรอง</CardTitle>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <!-- Search -->
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                v-model="search"
                                placeholder="ค้นหา..."
                                class="pl-9"
                            />
                        </div>

                        <!-- Action Filter -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="w-full justify-between">
                                    {{ selectedAction ? getActionLabel(selectedAction) : 'ประเภทการกระทำ' }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-48">
                                <DropdownMenuItem @click="selectedAction = ''; applyFilters()">
                                    ทั้งหมด
                                </DropdownMenuItem>
                                <DropdownMenuItem 
                                    v-for="action in actions" 
                                    :key="action"
                                    @click="selectedAction = action; applyFilters()"
                                >
                                    {{ getActionLabel(action) }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <!-- Model Type Filter -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <Button variant="outline" class="w-full justify-between">
                                    {{ selectedModelType ? modelTypes.find(t => t.value === selectedModelType)?.label : 'ประเภทข้อมูล' }}
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-48">
                                <DropdownMenuItem @click="selectedModelType = ''; applyFilters()">
                                    ทั้งหมด
                                </DropdownMenuItem>
                                <DropdownMenuItem 
                                    v-for="type in modelTypes" 
                                    :key="type.value"
                                    @click="selectedModelType = type.value; applyFilters()"
                                >
                                    {{ type.label }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <!-- Date From -->
                        <Input
                            v-model="dateFrom"
                            type="date"
                            placeholder="ตั้งแต่วันที่"
                            @change="applyFilters"
                        />

                        <!-- Date To -->
                        <Input
                            v-model="dateTo"
                            type="date"
                            placeholder="ถึงวันที่"
                            @change="applyFilters"
                        />
                    </div>

                    <div v-if="hasActiveFilters" class="mt-4 flex items-center gap-2">
                        <Button variant="outline" size="sm" @click="clearFilters">
                            ล้างตัวกรอง
                        </Button>
                        <span class="text-sm text-muted-foreground">
                            พบ {{ logs.total }} รายการ
                        </span>
                    </div>
                </CardContent>
            </Card>

            <!-- Activity Logs List -->
            <Card>
                <CardHeader>
                    <CardTitle>รายการกิจกรรม</CardTitle>
                    <CardDescription>
                        แสดง {{ logs.data.length }} จาก {{ logs.total }} รายการ
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="logs.data.length === 0" class="py-12 text-center">
                        <Activity class="mx-auto h-12 w-12 text-muted-foreground/50" />
                        <p class="mt-2 text-sm text-muted-foreground">ไม่พบรายการกิจกรรม</p>
                    </div>

                    <div v-else class="space-y-4">
                        <div
                            v-for="log in logs.data"
                            :key="log.id"
                            class="flex items-start gap-4 rounded-lg border p-4"
                        >
                            <!-- Action Icon -->
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full"
                                :class="getActionColor(log.action)"
                            >
                                <component :is="getActionIcon(log.action)" class="h-5 w-5" />
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-medium">
                                        {{ log.user?.name || 'ระบบ' }}
                                    </span>
                                    <Badge variant="outline" class="text-xs">
                                        {{ getActionLabel(log.action) }}
                                    </Badge>
                                    <Badge v-if="log.model_type_label" variant="secondary" class="text-xs">
                                        {{ log.model_type_label }}
                                    </Badge>
                                </div>
                                
                                <p class="mt-1 text-sm text-muted-foreground line-clamp-2">
                                    {{ log.description }}
                                </p>

                                <div class="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
                                    <span class="flex items-center gap-1">
                                        <Calendar class="h-3 w-3" />
                                        {{ formatDateTime(log.created_at) }}
                                    </span>
                                    <span v-if="log.ip_address" class="hidden sm:inline">
                                        IP: {{ log.ip_address }}
                                    </span>
                                </div>
                            </div>

                            <!-- Action -->
                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="getModelLink(log)"
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8"
                                    as-child
                                >
                                    <Link :href="getModelLink(log)!">
                                        <ExternalLink class="h-4 w-4" />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.last_page > 1" class="mt-6 flex items-center justify-center gap-2">
                        <Button
                            variant="outline"
                            size="icon"
                            :disabled="logs.current_page === 1"
                            @click="router.get(logs.links[0].url!)"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Button>
                        
                        <span class="text-sm text-muted-foreground">
                            หน้า {{ logs.current_page }} จาก {{ logs.last_page }}
                        </span>

                        <Button
                            variant="outline"
                            size="icon"
                            :disabled="logs.current_page === logs.last_page"
                            @click="router.get(logs.links[logs.links.length - 1].url!)"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
