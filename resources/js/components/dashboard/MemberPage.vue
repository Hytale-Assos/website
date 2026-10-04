<script setup lang="ts">
import { Info, UserRound } from '@lucide/vue';
import ApiErrorAlert from '@/components/dashboard/ApiErrorAlert.vue';
import Heading from '@/components/Heading.vue';
import { useLocale } from '@/composables/useLocale';

defineProps<{
    title: string;
    description?: string;
    error?: string | null;
    mock?: boolean;
    hytaleId?: string | null;
    requireHytale?: boolean;
}>();

const { t } = useLocale();
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading :title="title" :description="description" />
            <slot name="actions" />
        </div>

        <ApiErrorAlert :error="error ?? null" />

        <div
            v-if="mock"
            class="flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-2.5 text-sm text-amber-700 dark:text-amber-400"
        >
            <Info class="size-4 shrink-0" />
            {{ t('dash.mock.banner') }}
        </div>

        <div
            v-if="requireHytale && !hytaleId"
            class="bg-muted/40 border-border flex items-start gap-3 rounded-xl border px-4 py-3 text-sm"
        >
            <UserRound class="text-muted-foreground mt-0.5 size-4 shrink-0" />
            <div>
                <p class="font-medium">{{ t('dash.noHytaleId.title') }}</p>
                <p class="text-muted-foreground text-xs">
                    {{ t('dash.noHytaleId.description') }}
                </p>
            </div>
        </div>

        <slot />
    </div>
</template>
