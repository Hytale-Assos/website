<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import { RefreshCw } from '@lucide/vue';
import MemberPage from '@/components/dashboard/MemberPage.vue';
import ServersList from '@/components/dashboard/ServersList.vue';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { index as serversIndex } from '@/routes/servers';
import { store as whitelistStore } from '@/routes/whitelist';
import type { HytaleServer, WhitelistEntry } from '@/types';

const props = defineProps<{
    servers: HytaleServer[];
    whitelists: WhitelistEntry[];
    hytaleId: string | null;
    error: string | null;
    mock: boolean;
}>();

const { t } = useLocale();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [{ title: t('nav.items.servers'), href: serversIndex() }],
    });
});

const processingId = ref<string | null>(null);
const canJoin = computed(() => Boolean(props.hytaleId));

const joinServer = (server: HytaleServer) => {
    if (!canJoin.value || processingId.value) {
        return;
    }

    processingId.value = server.id;

    router.post(
        whitelistStore.url(),
        { hytale_server_id: server.id },
        {
            preserveScroll: true,
            onFinish: () => {
                processingId.value = null;
            },
        },
    );
};

const refresh = () => router.reload();
</script>

<template>
    <Head :title="t('dash.servers.title')" />

    <MemberPage
        :title="t('dash.servers.title')"
        :description="t('dash.servers.description')"
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

        <ServersList
            :servers="servers"
            :whitelists="whitelists"
            :can-join="canJoin"
            :processing-id="processingId"
            @join="joinServer"
        />
    </MemberPage>
</template>
