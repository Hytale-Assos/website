<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Construction, Package } from '@lucide/vue';
import { watchEffect } from 'vue';
import MemberPage from '@/components/dashboard/MemberPage.vue';
import EmptyState from '@/components/dashboard/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';
import { index as modsIndex } from '@/routes/mods';

defineProps<{
    hytaleId: string | null;
    mock: boolean;
}>();

const { t } = useLocale();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [{ title: t('nav.items.mods'), href: modsIndex() }],
    });
});
</script>

<template>
    <Head :title="t('dash.mods.title')" />

    <MemberPage
        :title="t('dash.mods.title')"
        :description="t('dash.mods.description')"
        :hytale-id="hytaleId"
        :mock="mock"
    >
        <EmptyState
            :icon="Package"
            :title="t('dash.mods.empty')"
            :description="t('dash.mods.comingSoon')"
        >
            <div class="mt-5">
                <span
                    class="text-muted-foreground inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium"
                >
                    <Construction class="size-3.5" />
                    {{ t('dash.mods.badge') }}
                </span>
            </div>
        </EmptyState>

        <div class="mt-6 flex justify-center">
            <Button variant="outline" disabled>
                {{ t('dash.mods.cta') }}
            </Button>
        </div>
    </MemberPage>
</template>
