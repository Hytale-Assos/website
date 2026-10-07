<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { ref, watchEffect } from 'vue';
import MemberPage from '@/components/dashboard/MemberPage.vue';
import RefreshButton from '@/components/dashboard/RefreshButton.vue';
import WhitelistList from '@/components/dashboard/WhitelistList.vue';
import { useLocale } from '@/composables/useLocale';
import {
    index as whitelistIndex,
    destroy as whitelistDestroy,
} from '@/routes/whitelist';
import type { HytaleServer, WhitelistEntry } from '@/types';

defineProps<{
    whitelists: WhitelistEntry[];
    servers: HytaleServer[];
    hytaleId: string | null;
    error: string | null;
    mock: boolean;
}>();

const { t } = useLocale();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            { title: t('nav.items.whitelist'), href: whitelistIndex() },
        ],
    });
});

const processingId = ref<string | null>(null);

const leaveEntry = (entry: WhitelistEntry) => {
    if (processingId.value) {
        return;
    }

    processingId.value = entry.id;

    router.delete(whitelistDestroy.url(entry.id), {
        preserveScroll: true,
        onFinish: () => {
            processingId.value = null;
        },
    });
};
</script>

<template>
    <Head :title="t('dash.whitelist.title')" />

    <MemberPage
        :title="t('dash.whitelist.title')"
        :description="t('dash.whitelist.description')"
        :error="error"
        :mock="mock"
        :hytale-id="hytaleId"
        require-hytale
    >
        <template #actions>
            <RefreshButton />
        </template>

        <WhitelistList
            :whitelists="whitelists"
            :servers="servers"
            :processing-id="processingId"
            @leave="leaveEntry"
        />
    </MemberPage>
</template>
