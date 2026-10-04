<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Award,
    BookOpen,
    CalendarClock,
    FolderGit2,
    LayoutGrid,
    Map as MapIcon,
    Package,
    Server,
    ShieldCheck,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as mapsIndex } from '@/routes/maps';
import { index as modsIndex } from '@/routes/mods';
import { index as pointsIndex } from '@/routes/points';
import { index as serversIndex } from '@/routes/servers';
import { index as sessionsIndex } from '@/routes/sessions';
import { index as whitelistIndex } from '@/routes/whitelist';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Servers',
        href: serversIndex(),
        icon: Server,
    },
    {
        title: 'Whitelist',
        href: whitelistIndex(),
        icon: ShieldCheck,
    },
    {
        title: 'Sessions',
        href: sessionsIndex(),
        icon: CalendarClock,
    },
    {
        title: 'Maps',
        href: mapsIndex(),
        icon: MapIcon,
    },
    {
        title: 'Mods',
        href: modsIndex(),
        icon: Package,
    },
    {
        title: 'Points Open',
        href: pointsIndex(),
        icon: Award,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
