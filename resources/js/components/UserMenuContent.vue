<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Languages, LogOut, Settings } from '@lucide/vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { Switch } from '@/components/ui/switch';
import UserInfo from '@/components/UserInfo.vue';
import { useLocale } from '@/composables/useLocale';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';

type Props = {
    user: User;
};

const { locale, t, setLocale } = useLocale();

const toggleLocale = (english: boolean) => {
    setLocale(english ? 'en' : 'fr');
};

const handleLogout = () => {
    router.flushAll();
};

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                {{ t('userMenu.settings') }}
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <div class="flex items-center justify-between gap-2 px-2 py-1.5">
        <span class="flex items-center text-sm font-medium">
            <Languages class="mr-2 h-4 w-4" />
            {{ t('userMenu.language') }}
        </span>
        <div class="flex items-center gap-2">
            <span
                class="text-xs font-medium transition-colors"
                :class="
                    locale === 'fr'
                        ? 'text-foreground'
                        : 'text-muted-foreground'
                "
            >
                FR
            </span>
            <Switch
                :model-value="locale === 'en'"
                :aria-label="t('userMenu.language')"
                @update:model-value="toggleLocale"
            />
            <span
                class="text-xs font-medium transition-colors"
                :class="
                    locale === 'en'
                        ? 'text-foreground'
                        : 'text-muted-foreground'
                "
            >
                EN
            </span>
        </div>
    </div>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            {{ t('userMenu.logout') }}
        </Link>
    </DropdownMenuItem>
</template>
