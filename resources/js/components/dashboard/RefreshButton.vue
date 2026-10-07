<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { RefreshCw } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useLocale } from '@/composables/useLocale';

const { t } = useLocale();

const isRefreshing = ref(false);

const refresh = () => {
    if (isRefreshing.value) {
        return;
    }

    isRefreshing.value = true;

    router.reload({
        onFinish: () => {
            isRefreshing.value = false;
        },
    });
};
</script>

<template>
    <Button
        variant="outline"
        size="sm"
        :disabled="isRefreshing"
        @click="refresh"
    >
        <RefreshCw
            class="size-4 transition-transform"
            :class="{ 'animate-spin': isRefreshing }"
        />
        {{ t('dash.refresh') }}
    </Button>
</template>
