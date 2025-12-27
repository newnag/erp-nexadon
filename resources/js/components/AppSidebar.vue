<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Boxes, LayoutGrid, Package, Settings, ShoppingCart, Utensils, Wallet } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'แดชบอร์ด',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

const inventoryNavItems: NavItem[] = [
    {
        title: 'วัตถุดิบ',
        href: '/ingredients',
        icon: Package,
    },
    {
        title: 'จัดการสต็อค',
        href: '/stock',
        icon: Boxes,
    },
    {
        title: 'ซัพพลายเออร์',
        href: '/settings/suppliers',
        icon: ShoppingCart,
    },
];

const productionNavItems: NavItem[] = [
    {
        title: 'สูตรอาหาร (SOP)',
        href: '/recipes',
        icon: Utensils,
    },
];

const financeNavItems: NavItem[] = [
    {
        title: 'รายรับรายจ่าย',
        href: '/finance',
        icon: Wallet,
    },
];

const settingsNavItems: NavItem[] = [
    {
        title: 'หน่วยวัด',
        href: '/settings/units',
        icon: Settings,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            
            <SidebarGroup>
                <SidebarGroupLabel>คลังสินค้า</SidebarGroupLabel>
                <SidebarGroupContent>
                    <NavMain :items="inventoryNavItems" />
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarGroup>
                <SidebarGroupLabel>การผลิต</SidebarGroupLabel>
                <SidebarGroupContent>
                    <NavMain :items="productionNavItems" />
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarGroup>
                <SidebarGroupLabel>การเงิน</SidebarGroupLabel>
                <SidebarGroupContent>
                    <NavMain :items="financeNavItems" />
                </SidebarGroupContent>
            </SidebarGroup>

            <SidebarGroup>
                <SidebarGroupLabel>การตั้งค่า</SidebarGroupLabel>
                <SidebarGroupContent>
                    <NavMain :items="settingsNavItems" />
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
