<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useLocale } from '@/composables/useLocale';
import { toUrl } from '@/lib/utils';
import { edit as editAccounts } from '@/routes/accounts';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editData } from '@/routes/data';
import { edit as editIdentity } from '@/routes/identity';
import { edit as editInvitations } from '@/routes/invitations';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { t } = useLocale();

const sidebarNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: t('settings.nav.profile'),
            href: editProfile(),
        },
        {
            title: t('settings.nav.identity'),
            href: editIdentity(),
        },
        {
            title: t('settings.nav.security'),
            href: editSecurity(),
        },
        {
            title: t('settings.nav.accounts'),
            href: editAccounts(),
        },
    ];

    if (user.value?.is_internal) {
        items.push({
            title: t('settings.nav.invitations'),
            href: editInvitations(),
        });
    }

    items.push(
        {
            title: t('settings.nav.data'),
            href: editData(),
        },
        {
            title: t('settings.nav.appearance'),
            href: editAppearance(),
        },
    );

    return items;
});

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            :title="t('settings.title')"
            :description="t('settings.description')"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    :aria-label="t('settings.title')"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
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
