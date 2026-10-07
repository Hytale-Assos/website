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
import { computed } from 'vue';
import { dashboard } from '@/routes';
import { index as mapsIndex } from '@/routes/maps';
import { index as modsIndex } from '@/routes/mods';
import { index as pointsIndex } from '@/routes/points';
import { index as serversIndex } from '@/routes/servers';
import { index as sessionsIndex } from '@/routes/sessions';
import { index as whitelistIndex } from '@/routes/whitelist';
import { useLocale } from '@/composables/useLocale';
import type { NavItem } from '@/types';

const { t } = useLocale();

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.items.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: t('nav.items.servers'),
        href: serversIndex(),
        icon: Server,
    },
    {
        title: t('nav.items.whitelist'),
        href: whitelistIndex(),
        icon: ShieldCheck,
    },
    {
        title: t('nav.items.sessions'),
        href: sessionsIndex(),
        icon: CalendarClock,
    },
    {
        title: t('nav.items.maps'),
        href: mapsIndex(),
        icon: MapIcon,
    },
    {
        title: t('nav.items.mods'),
        href: modsIndex(),
        icon: Package,
    },
    {
        title: t('nav.items.points'),
        href: pointsIndex(),
        icon: Award,
    },
]);

const footerNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.items.repository'),
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: t('nav.items.documentation'),
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
]);
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
