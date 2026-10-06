<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Construction, ExternalLink, Map as MapIcon } from '@lucide/vue';
import MemberPage from '@/components/dashboard/MemberPage.vue';
import EmptyState from '@/components/dashboard/EmptyState.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useLocale } from '@/composables/useLocale';
import { index as mapsIndex } from '@/routes/maps';
import type { HytaleServer } from '@/types';

const props = defineProps<{
    servers: HytaleServer[];
    hytaleId: string | null;
    error: string | null;
    mock: boolean;
}>();

const { t } = useLocale();

const selectedId = ref<string | undefined>(undefined);

const selectedServer = computed(() =>
    props.servers.find((server) => server.id === selectedId.value),
);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Maps', href: mapsIndex() }],
    },
});
</script>

<template>
    <Head :title="t('dash.maps.title')" />

    <MemberPage
        :title="t('dash.maps.title')"
        :description="t('dash.maps.description')"
        :error="error"
        :hytale-id="hytaleId"
        :mock="mock"
    >
        <template #actions>
            <div class="w-full sm:w-64">
                <Select v-model="selectedId">
                    <SelectTrigger class="w-full">
                        <SelectValue
                            :placeholder="t('dash.maps.serverPlaceholder')"
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="server in servers"
                            :key="server.id"
                            :value="server.id"
                        >
                            {{ server.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </template>

        <div
            class="border-border/70 bg-muted/20 relative flex min-h-[420px] flex-col items-center justify-center overflow-hidden rounded-xl border"
        >
            <div
                class="pointer-events-none absolute inset-0 opacity-[0.35]"
                aria-hidden="true"
                style="
                    background-image:
                        linear-gradient(
                            to right,
                            currentColor 1px,
                            transparent 1px
                        ),
                        linear-gradient(
                            to bottom,
                            currentColor 1px,
                            transparent 1px
                        );
                    background-size: 40px 40px;
                    color: var(--border);
                "
            ></div>

            <EmptyState
                class="relative border-none"
                :icon="MapIcon"
                :title="t('dash.maps.empty')"
                :description="t('dash.maps.comingSoon')"
            >
                <div
                    class="mt-5 flex flex-wrap items-center justify-center gap-3"
                >
                    <span
                        class="text-muted-foreground inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium"
                    >
                        <Construction class="size-3.5" />
                        {{ t('dash.maps.badge') }}
                    </span>

                    <a
                        v-if="selectedServer?.url"
                        :href="selectedServer.url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <Button variant="outline" size="sm">
                            <ExternalLink class="size-4" />
                            {{ t('dash.maps.openExternal') }}
                        </Button>
                    </a>
                </div>
            </EmptyState>
        </div>
    </MemberPage>
</template>
