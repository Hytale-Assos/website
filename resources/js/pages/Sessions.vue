<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import MemberPage from '@/components/dashboard/MemberPage.vue';
import RefreshButton from '@/components/dashboard/RefreshButton.vue';
import SessionsList from '@/components/dashboard/SessionsList.vue';
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

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.items.sessions'), href: sessionsIndex() },
        ],
    });
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
            <RefreshButton />
        </template>

        <SessionsList :sessions="sessions" :servers="servers" />
    </MemberPage>
</template>
