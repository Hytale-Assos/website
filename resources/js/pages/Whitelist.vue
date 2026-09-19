<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { RefreshCw } from '@lucide/vue';
import MemberPage from '@/components/dashboard/MemberPage.vue';
import WhitelistList from '@/components/dashboard/WhitelistList.vue';
import { Button } from '@/components/ui/button';
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

const refresh = () => router.reload();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Whitelist', href: whitelistIndex() }],
    },
});
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
            <Button variant="outline" size="sm" @click="refresh">
                <RefreshCw class="size-4" />
                {{ t('dash.refresh') }}
            </Button>
        </template>

        <WhitelistList
            :whitelists="whitelists"
            :servers="servers"
            :processing-id="processingId"
            @leave="leaveEntry"
        />
    </MemberPage>
</template>
