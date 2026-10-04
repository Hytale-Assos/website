<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';

defineProps<{
    icon: LucideIcon;
    title: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="border-border/70 flex flex-col items-center justify-center rounded-xl border border-dashed px-6 py-14 text-center"
    >
        <span
            class="bg-muted text-muted-foreground flex size-12 items-center justify-center rounded-full"
        >
            <component :is="icon" class="size-5" />
        </span>
        <p class="mt-4 font-medium">{{ title }}</p>
        <p
            v-if="description"
            class="text-muted-foreground mt-1 max-w-sm text-sm"
        >
            {{ description }}
        </p>
        <slot />
    </div>
</template>
