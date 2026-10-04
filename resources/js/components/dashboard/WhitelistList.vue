<script setup lang="ts">
import { Loader2, ShieldCheck, Trash } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/dashboard/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import { formatRelative } from '@/lib/hytale';
import type { HytaleServer, WhitelistEntry } from '@/types';

const props = defineProps<{
    whitelists: WhitelistEntry[];
    servers: HytaleServer[];
    processingId: string | null;
}>();

defineEmits<{
    leave: [entry: WhitelistEntry];
}>();

const { t } = useLocale();

const serverName = (serverId: string): string =>
    props.servers.find((server) => server.id === serverId)?.name ?? serverId;

const entries = computed(() => props.whitelists);
</script>

<template>
    <div class="space-y-4">
        <EmptyState
            v-if="entries.length === 0"
            :icon="ShieldCheck"
            :title="t('dash.whitelist.empty')"
        />

        <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Card v-for="entry in entries" :key="entry.id">
                <CardContent class="flex items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3">
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500"
                        >
                            <ShieldCheck class="size-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-medium">
                                {{ serverName(entry.hytale_server_id) }}
                            </p>
                            <p class="text-muted-foreground text-xs">
                                {{ t('dash.whitelist.since') }}
                                {{ formatRelative(entry.created_at) }}
                            </p>
                        </div>
                    </div>

                    <Button
                        variant="ghost"
                        size="icon"
                        class="text-muted-foreground hover:text-destructive shrink-0"
                        :disabled="processingId === entry.id"
                        :aria-label="t('dash.whitelist.leave')"
                        @click="$emit('leave', entry)"
                    >
                        <Loader2
                            v-if="processingId === entry.id"
                            class="size-4 animate-spin"
                        />
                        <Trash v-else class="size-4" />
                    </Button>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
