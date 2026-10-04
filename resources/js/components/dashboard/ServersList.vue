<script setup lang="ts">
import { Server } from '@lucide/vue';
import { computed } from 'vue';
import ServerCard from '@/components/dashboard/ServerCard.vue';
import EmptyState from '@/components/dashboard/EmptyState.vue';
import { useLocale } from '@/composables/useLocale';
import type { HytaleServer, WhitelistEntry } from '@/types';

const props = defineProps<{
    servers: HytaleServer[];
    whitelists: WhitelistEntry[];
    canJoin: boolean;
    processingId: string | null;
}>();

defineEmits<{
    join: [server: HytaleServer];
}>();

const { t } = useLocale();

const whitelistedServerIds = computed(
    () => new Set(props.whitelists.map((entry) => entry.hytale_server_id)),
);
</script>

<template>
    <div class="space-y-4">
        <EmptyState
            v-if="servers.length === 0"
            :icon="Server"
            :title="t('dash.servers.empty')"
        />

        <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <ServerCard
                v-for="server in servers"
                :key="server.id"
                :server="server"
                :joined="whitelistedServerIds.has(server.id)"
                :can-join="canJoin"
                :processing="processingId === server.id"
                @join="$emit('join', $event)"
            />
        </div>
    </div>
</template>
