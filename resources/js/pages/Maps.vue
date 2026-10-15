<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Construction, Map as MapIcon } from '@lucide/vue';
import { watchEffect } from 'vue';
import MemberPage from '@/components/dashboard/MemberPage.vue';
import EmptyState from '@/components/dashboard/EmptyState.vue';
import { useLocale } from '@/composables/useLocale';
import { index as mapsIndex } from '@/routes/maps';

defineProps<{
    hytaleId: string | null;
    mock: boolean;
}>();

const { t } = useLocale();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [{ title: t('nav.items.maps'), href: mapsIndex() }],
    });
});
</script>

<template>
    <Head :title="t('dash.maps.title')" />

    <MemberPage
        :title="t('dash.maps.title')"
        :description="t('dash.maps.description')"
        :hytale-id="hytaleId"
        :mock="mock"
    >
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
                </div>
            </EmptyState>
        </div>
    </MemberPage>
</template>
