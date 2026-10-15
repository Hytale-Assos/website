<script setup lang="ts">
import { computed } from 'vue';
import { useAccessibility } from '@/composables/useAccessibility';
import { useLocale } from '@/composables/useLocale';
import type { FontSize } from '@/composables/useAccessibility';

const { fontSize, updateFontSize } = useAccessibility();
const { t } = useLocale();

const tabs = computed(
    () =>
        [
            { value: 'system', label: t('accessibility.font.system') },
            { value: 's', label: '90 %' },
            { value: 'm', label: '100 %' },
            { value: 'l', label: '110 %' },
            { value: 'xl', label: '125 %' },
            { value: 'xxl', label: '150 %' },
        ] as const satisfies ReadonlyArray<{ value: FontSize; label: string }>,
);
</script>

<template>
    <div
        class="inline-flex flex-wrap gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800"
    >
        <button
            v-for="{ value, label } in tabs"
            :key="value"
            @click="updateFontSize(value)"
            :class="[
                'flex items-center rounded-md px-3 py-1.5 text-sm transition-colors',
                fontSize === value
                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
            ]"
        >
            {{ label }}
        </button>
    </div>
</template>
