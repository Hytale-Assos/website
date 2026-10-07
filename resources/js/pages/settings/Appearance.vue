<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { useLocale } from '@/composables/useLocale';
import { edit } from '@/routes/appearance';

const { t } = useLocale();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('settings.appearance.head'),
                href: edit(),
            },
        ],
    });
});
</script>

<template>
    <Head :title="t('settings.appearance.head')" />

    <h1 class="sr-only">{{ t('settings.appearance.head') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.appearance.title')"
            :description="t('settings.appearance.description')"
        />
        <AppearanceTabs />
    </div>
</template>
