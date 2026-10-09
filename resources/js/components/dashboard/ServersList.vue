<script setup lang="ts">
import { Server } from '@lucide/vue';
import EmptyState from '@/components/dashboard/EmptyState.vue';
import ServerCard from '@/components/dashboard/ServerCard.vue';
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
    leave: [entry: WhitelistEntry];
}>();

const { t } = useLocale();

const entryFor = (serverId: string): WhitelistEntry | null =>
    props.whitelists.find((entry) => entry.hytale_server_id === serverId) ??
    null;
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
                :entry="entryFor(server.id)"
                :can-join="canJoin"
                :processing="
                    processingId === server.id ||
                    processingId === entryFor(server.id)?.id
                "
                @join="$emit('join', $event)"
                @leave="$emit('leave', $event)"
            />
        </div>
    </div>
</template>
