<script setup lang="ts">
import { useClipboard } from '@vueuse/core';
import { Check, Copy, Gamepad2, Loader2, UserPlus } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useLocale } from '@/composables/useLocale';
import type { HytaleServer } from '@/types';

const props = defineProps<{
    server: HytaleServer;
    joined: boolean;
    canJoin: boolean;
    processing?: boolean;
}>();

defineEmits<{
    join: [server: HytaleServer];
}>();

const { t } = useLocale();

const { copy, copied } = useClipboard({ source: () => props.server.url });
</script>

<template>
    <Card class="transition-colors hover:border-emerald-500/40">
        <CardContent class="flex flex-col gap-4">
            <div class="flex items-start justify-between gap-3">
                <span
                    class="border-border bg-background flex size-11 shrink-0 items-center justify-center rounded-xl border"
                >
                    <Gamepad2 class="size-5 text-emerald-500" />
                </span>

                <Badge v-if="joined" variant="secondary" class="gap-1.5">
                    <span
                        class="size-1.5 rounded-full bg-emerald-500"
                        aria-hidden="true"
                    />
                    {{ t('dash.servers.joined') }}
                </Badge>
            </div>

            <div class="min-w-0">
                <h3 class="truncate font-semibold">{{ server.name }}</h3>
                <p class="text-muted-foreground text-xs">
                    {{ t('dash.servers.address') }}
                </p>
                <code
                    class="bg-muted/50 mt-1 block truncate rounded-md px-2 py-1 font-mono text-xs"
                    :title="server.url"
                >
                    {{ server.url }}
                </code>
            </div>

            <div class="mt-auto flex flex-wrap gap-2">
                <Button
                    v-if="!joined"
                    size="sm"
                    :disabled="!canJoin || processing"
                    @click="$emit('join', server)"
                >
                    <Loader2 v-if="processing" class="size-4 animate-spin" />
                    <UserPlus v-else class="size-4" />
                    {{ t('dash.servers.join') }}
                </Button>

                <Button variant="outline" size="sm" @click="copy(server.url)">
                    <Check v-if="copied" class="size-4 text-emerald-500" />
                    <Copy v-else class="size-4" />
                    {{
                        copied
                            ? t('dash.servers.copied')
                            : t('dash.servers.copy')
                    }}
                </Button>
            </div>

            <p class="text-muted-foreground text-xs">
                {{ t('dash.servers.enterInGame') }}
            </p>
        </CardContent>
    </Card>
</template>
