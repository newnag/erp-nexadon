<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Link, router } from '@inertiajs/vue3';
import { Bell, CheckCheck, ExternalLink } from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

interface ActivityLog {
    id: number;
    user: {
        id: number;
        name: string;
    } | null;
}

interface Notification {
    id: number;
    title: string;
    message: string;
    type: 'info' | 'success' | 'warning' | 'error';
    link: string | null;
    read_at: string | null;
    created_at: string;
    activity_log: ActivityLog | null;
}

const notifications = ref<Notification[]>([]);
const unreadCount = ref(0);
const loading = ref(false);
const isOpen = ref(false);

let pollInterval: ReturnType<typeof setInterval> | null = null;

const fetchNotifications = async () => {
    try {
        loading.value = true;
        const response = await axios.get('/notifications/recent');
        notifications.value = response.data.notifications;
        unreadCount.value = response.data.unread_count;
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    } finally {
        loading.value = false;
    }
};

const markAsRead = async (notification: Notification) => {
    if (notification.read_at) return;
    
    try {
        await axios.post(`/notifications/${notification.id}/read`);
        notification.read_at = new Date().toISOString();
        unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/notifications/mark-all-read');
        notifications.value.forEach(n => n.read_at = new Date().toISOString());
        unreadCount.value = 0;
    } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
    }
};

const handleNotificationClick = async (notification: Notification) => {
    await markAsRead(notification);
    if (notification.link) {
        router.visit(notification.link);
    }
};

const formatTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);
    
    if (minutes < 1) return 'เมื่อสักครู่';
    if (minutes < 60) return `${minutes} นาทีที่แล้ว`;
    if (hours < 24) return `${hours} ชั่วโมงที่แล้ว`;
    if (days < 7) return `${days} วันที่แล้ว`;
    
    return date.toLocaleDateString('th-TH', {
        day: 'numeric',
        month: 'short',
    });
};

const getTypeColor = (type: string) => {
    switch (type) {
        case 'success': return 'bg-green-500';
        case 'warning': return 'bg-amber-500';
        case 'error': return 'bg-red-500';
        default: return 'bg-blue-500';
    }
};

onMounted(() => {
    fetchNotifications();
    // Poll every 30 seconds
    pollInterval = setInterval(fetchNotifications, 30000);
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});

const onOpenChange = (open: boolean) => {
    isOpen.value = open;
    if (open) {
        fetchNotifications();
    }
};
</script>

<template>
    <DropdownMenu @update:open="onOpenChange">
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="group relative h-9 w-9 cursor-pointer"
            >
                <Bell class="size-5 opacity-80 group-hover:opacity-100" />
                <span
                    v-if="unreadCount > 0"
                    class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80 max-h-96 overflow-y-auto">
            <div class="flex items-center justify-between px-3 py-2 border-b">
                <h4 class="font-semibold text-sm">การแจ้งเตือน</h4>
                <Button
                    v-if="unreadCount > 0"
                    variant="ghost"
                    size="sm"
                    class="h-7 text-xs"
                    @click="markAllAsRead"
                >
                    <CheckCheck class="mr-1 h-3 w-3" />
                    อ่านทั้งหมด
                </Button>
            </div>
            
            <div v-if="loading && notifications.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                กำลังโหลด...
            </div>
            
            <div v-else-if="notifications.length === 0" class="p-4 text-center text-sm text-muted-foreground">
                ไม่มีการแจ้งเตือน
            </div>
            
            <template v-else>
                <DropdownMenuItem
                    v-for="notification in notifications"
                    :key="notification.id"
                    class="flex flex-col items-start gap-1 p-3 cursor-pointer"
                    :class="{ 'bg-muted/50': !notification.read_at }"
                    @click="handleNotificationClick(notification)"
                >
                    <div class="flex w-full items-start gap-2">
                        <div
                            class="mt-1.5 h-2 w-2 rounded-full shrink-0"
                            :class="getTypeColor(notification.type)"
                        ></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium line-clamp-1">
                                {{ notification.title }}
                            </p>
                            <p class="text-xs text-muted-foreground line-clamp-2">
                                {{ notification.message }}
                            </p>
                            <p class="text-[10px] text-muted-foreground mt-1">
                                {{ formatTime(notification.created_at) }}
                            </p>
                        </div>
                        <ExternalLink v-if="notification.link" class="h-3 w-3 text-muted-foreground shrink-0" />
                    </div>
                </DropdownMenuItem>
            </template>
            
            <DropdownMenuSeparator />
            <DropdownMenuItem as-child class="justify-center">
                <Link href="/activity-logs" class="text-xs text-center w-full">
                    ดูประวัติกิจกรรมทั้งหมด
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
