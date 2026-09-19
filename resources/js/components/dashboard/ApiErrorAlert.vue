<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { useLocale } from '@/composables/useLocale';

defineProps<{
    error: string | null;
}>();

const { t } = useLocale();
</script>

<template>
    <Alert v-if="error" variant="destructive">
        <TriangleAlert />
        <AlertTitle>{{ t('dash.error.title') }}</AlertTitle>
        <AlertDescription>{{ error }}</AlertDescription>
    </Alert>
</template>
