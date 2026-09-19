<script setup lang="ts">
import { CalendarClock, Clock, LogIn, LogOut } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import EmptyState from '@/components/dashboard/EmptyState.vue';
import { useLocale } from '@/composables/useLocale';
import { formatDateTime, formatDuration } from '@/lib/hytale';
import type { HytaleServer, PlayerSession } from '@/types';

const props = defineProps<{
    sessions: PlayerSession[];
    servers: HytaleServer[];
}>();

const { t } = useLocale();

const serverName = (serverId: string): string =>
    props.servers.find((server) => server.id === serverId)?.name ?? serverId;
</script>

<template>
    <div class="space-y-4">
        <EmptyState
            v-if="sessions.length === 0"
            :icon="CalendarClock"
            :title="t('dash.sessions.empty')"
        />

        <div v-else class="space-y-3">
            <Card v-for="session in sessions" :key="session.id">
                <CardContent class="flex flex-wrap items-center gap-4">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg"
                        :class="
                            session.ended_at
                                ? 'bg-muted text-muted-foreground'
                                : 'bg-emerald-500/10 text-emerald-500'
                        "
                    >
                        <Clock class="size-5" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate font-medium">
                                {{ serverName(session.hytale_server_id) }}
                            </p>
                            <Badge
                                :variant="
                                    session.ended_at ? 'secondary' : 'default'
                                "
                            >
                                {{
                                    session.ended_at
                                        ? t('dash.sessions.closed')
                                        : t('dash.sessions.open')
                                }}
                            </Badge>
                        </div>
                        <p class="text-muted-foreground mt-0.5 text-xs">
                            {{ t('dash.sessions.duration') }} :
                            {{
                                formatDuration(
                                    session.joined_at,
                                    session.ended_at,
                                ) ?? '—'
                            }}
                        </p>
                    </div>

                    <div class="text-muted-foreground space-y-1 text-xs">
                        <p class="flex items-center gap-1.5">
                            <LogIn class="size-3.5" />
                            {{ formatDateTime(session.joined_at) }}
                        </p>
                        <p class="flex items-center gap-1.5">
                            <LogOut class="size-3.5" />
                            {{ formatDateTime(session.ended_at) }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
