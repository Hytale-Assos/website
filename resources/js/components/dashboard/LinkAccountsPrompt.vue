<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useLocale } from '@/composables/useLocale';
import { edit as accountsEdit } from '@/routes/accounts';

const DISMISSED_KEY = 'linkAccountsPromptDismissed';

const { t } = useLocale();
const page = usePage();

const user = computed(() => page.props.auth.user);
const isHytaleMissing = computed(() => !user.value.hytale_id);
const isDiscordMissing = computed(() => !user.value.discord_id);

const open = ref(false);

const shouldShow = (): boolean => {
    if (typeof window === 'undefined') {
        return false;
    }

    if (sessionStorage.getItem(DISMISSED_KEY) !== null) {
        return false;
    }

    return isHytaleMissing.value || isDiscordMissing.value;
};

const dismiss = (): void => {
    sessionStorage.setItem(DISMISSED_KEY, '1');
};

open.value = shouldShow();
</script>

<template>
    <Dialog
        :open="open"
        @update:open="
            (value) => {
                if (!value) {
                    dismiss();
                }
                open = value;
            }
        "
    >
        <DialogContent data-test="link-accounts-prompt">
            <DialogHeader>
                <DialogTitle>{{ t('dash.linkPrompt.title') }}</DialogTitle>
                <DialogDescription>
                    {{ t('dash.linkPrompt.description') }}
                    <ul class="mt-1 list-inside list-disc">
                        <li v-if="isHytaleMissing">
                            {{ t('dash.linkPrompt.hytaleMissing') }}
                        </li>
                        <li v-if="isDiscordMissing">
                            {{ t('dash.linkPrompt.discordMissing') }}
                        </li>
                    </ul>
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button
                    variant="outline"
                    data-test="link-accounts-prompt-later"
                    @click="open = false"
                >
                    {{ t('dash.linkPrompt.later') }}
                </Button>
                <Button
                    as-child
                    data-test="link-accounts-prompt-yes"
                    @click="dismiss"
                >
                    <Link :href="accountsEdit()">
                        {{ t('dash.linkPrompt.yes') }}
                    </Link>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
