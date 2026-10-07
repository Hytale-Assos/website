<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="72 32 176 276"
        fill="none"
        :class="className"
        v-bind="$attrs"
    >
        <path
            d="M180 300 240 240 240 40 180 40 180 131 140 131 140 40 80 40 80 240 140 280 140 189 180 189 180 300"
            fill="none"
            stroke="currentColor"
            stroke-width="16"
            stroke-linecap="round"
        />
    </svg>
</template>
