<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import FontSizeTabs from '@/components/FontSizeTabs.vue';
import Heading from '@/components/Heading.vue';
import { useLocale } from '@/composables/useLocale';
import { edit } from '@/routes/accessibility';

const { t } = useLocale();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('settings.accessibility.head'),
                href: edit(),
            },
        ],
    });
});
</script>

<template>
    <Head :title="t('settings.accessibility.head')" />

    <h1 class="sr-only">{{ t('settings.accessibility.head') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.accessibility.font.title')"
            :description="t('settings.accessibility.font.description')"
        />
        <FontSizeTabs />
    </div>
</template>
