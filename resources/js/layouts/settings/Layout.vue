<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { toUrl, urlIsActive } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

interface NavSection {
    title: string;
    items: NavItem[];
}

const accountNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Password',
        href: editPassword(),
    },
    {
        title: 'Two-Factor Auth',
        href: show(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
];

const websiteNavItems: NavItem[] = [
    {
        title: 'Suppliers',
        href: '/settings/suppliers',
    },
    {
        title: 'Units',
        href: '/settings/units',
    },
];

const navSections: NavSection[] = [
    {
        title: 'Account Settings',
        items: accountNavItems,
    },
    {
        title: 'Website Settings',
        items: websiteNavItems,
    },
];

const currentPath = typeof window !== undefined ? window.location.pathname : '';
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-56">
                <nav class="flex flex-col space-y-6 space-x-0">
                    <div v-for="section in navSections" :key="section.title" class="space-y-1">
                        <h3 class="px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-2">
                            {{ section.title }}
                        </h3>
                        <Button
                            v-for="item in section.items"
                            :key="toUrl(item.href)"
                            variant="ghost"
                            :class="[
                                'w-full justify-start',
                                { 'bg-muted': urlIsActive(item.href, currentPath) },
                            ]"
                            as-child
                        >
                            <Link :href="item.href">
                                <component :is="item.icon" class="h-4 w-4" />
                                {{ item.title }}
                            </Link>
                        </Button>
                    </div>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
