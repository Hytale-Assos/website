<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import FontSizeTabs from '@/components/FontSizeTabs.vue';
import Heading from '@/components/Heading.vue';
import { Switch } from '@/components/ui/switch';
import { useAccessibility } from '@/composables/useAccessibility';
import { useLocale } from '@/composables/useLocale';
import { edit } from '@/routes/accessibility';

const { t } = useLocale();
const { dyslexicFont, updateDyslexicFont } = useAccessibility();

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

        <Heading
            variant="small"
            :title="t('settings.accessibility.dyslexic.title')"
            :description="t('settings.accessibility.dyslexic.description')"
        />
        <Switch
            :model-value="dyslexicFont"
            @update:model-value="updateDyslexicFont"
            :aria-label="t('settings.accessibility.dyslexic.title')"
        />
    </div>
</template>
