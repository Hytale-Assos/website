<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarClock,
    Server,
    ShieldCheck,
    UserRound,
} from '@lucide/vue';
import { watchEffect } from 'vue';
import MemberPage from '@/components/dashboard/MemberPage.vue';
import LinkAccountsPrompt from '@/components/dashboard/LinkAccountsPrompt.vue';
import RefreshButton from '@/components/dashboard/RefreshButton.vue';
import SessionsList from '@/components/dashboard/SessionsList.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import { dashboard } from '@/routes';
import { index as serversIndex } from '@/routes/servers';
import { index as sessionsIndex } from '@/routes/sessions';
import { index as whitelistIndex } from '@/routes/whitelist';
import type { HytaleServer, PlayerSession } from '@/types';

defineProps<{
    counts: {
        servers: number;
        whitelists: number;
        sessions: number;
    };
    recentSessions: PlayerSession[];
    servers: HytaleServer[];
    hytaleId: string | null;
    error: string | null;
    mock: boolean;
}>();

const { t } = useLocale();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [{ title: t('nav.items.dashboard'), href: dashboard() }],
    });
});

const cards = [
    {
        key: 'dash.tabs.servers',
        prop: 'servers',
        icon: Server,
        href: serversIndex,
    },
    {
        key: 'dash.tabs.whitelist',
        prop: 'whitelists',
        icon: ShieldCheck,
        href: whitelistIndex,
    },
    {
        key: 'dash.tabs.sessions',
        prop: 'sessions',
        icon: CalendarClock,
        href: sessionsIndex,
    },
] as const;
</script>

<template>
    <Head :title="t('dash.title')" />

    <LinkAccountsPrompt />

    <MemberPage
        :title="t('dash.title')"
        :description="t('dash.description')"
        :error="error"
        :mock="mock"
        :hytale-id="hytaleId"
    >
        <template #actions>
            <div class="flex items-center gap-2">
                <Badge v-if="hytaleId" variant="outline" class="gap-1.5">
                    <UserRound class="size-3.5" />
                    {{ t('dash.account.connected') }}
                </Badge>
                <RefreshButton />
            </div>
        </template>

        <div class="grid gap-4 sm:grid-cols-3">
            <Link
                v-for="card in cards"
                :key="card.key"
                :href="card.href()"
                class="group"
            >
                <Card
                    class="transition-colors group-hover:border-emerald-500/40"
                >
                    <CardContent class="flex items-center justify-between">
                        <div>
                            <p
                                class="text-muted-foreground flex items-center gap-1.5 text-xs font-medium"
                            >
                                <component :is="card.icon" class="size-3.5" />
                                {{ t(card.key) }}
                            </p>
                            <p class="mt-1 text-3xl font-bold tracking-tight">
                                {{ counts[card.prop] }}
                            </p>
                        </div>
                        <ArrowRight
                            class="text-muted-foreground size-4 transition-transform group-hover:translate-x-0.5"
                        />
                    </CardContent>
                </Card>
            </Link>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold">
                    {{ t('dash.sessions.title') }}
                </h2>
                <Link
                    :href="sessionsIndex()"
                    class="text-muted-foreground hover:text-foreground text-xs font-medium"
                >
                    {{ t('dash.activity.viewAll') }}
                </Link>
            </div>

            <SessionsList :sessions="recentSessions" :servers="servers" />
        </div>
    </MemberPage>
</template>
