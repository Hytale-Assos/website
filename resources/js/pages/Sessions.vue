<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { RefreshCw } from '@lucide/vue';
import MemberPage from '@/components/dashboard/MemberPage.vue';
import SessionsList from '@/components/dashboard/SessionsList.vue';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { index as sessionsIndex } from '@/routes/sessions';
import type { HytaleServer, PlayerSession } from '@/types';

defineProps<{
    sessions: PlayerSession[];
    servers: HytaleServer[];
    hytaleId: string | null;
    error: string | null;
    mock: boolean;
}>();

const { t } = useLocale();

const refresh = () => router.reload();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Sessions', href: sessionsIndex() }],
    },
});
</script>

<template>
    <Head :title="t('dash.sessions.title')" />

    <MemberPage
        :title="t('dash.sessions.title')"
        :description="t('dash.sessions.description')"
        :error="error"
        :mock="mock"
        :hytale-id="hytaleId"
        require-hytale
    >
        <template #actions>
            <Button variant="outline" size="sm" @click="refresh">
                <RefreshCw class="size-4" />
                {{ t('dash.refresh') }}
            </Button>
        </template>

        <SessionsList :sessions="sessions" :servers="servers" />
    </MemberPage>
</template>
