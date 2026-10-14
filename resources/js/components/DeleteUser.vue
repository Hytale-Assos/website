<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/composables/useLocale';

const CONFIRMATION_SECONDS = 10;

const passwordInput = useTemplateRef('passwordInput');
const { t } = useLocale();

const confirmPhrase = t('deleteUser.confirmPhrase');
const open = ref(false);
const secondsLeft = ref(0);
const confirmation = ref('');
let timer: ReturnType<typeof setInterval> | undefined;

const canSubmit = computed(
    () =>
        secondsLeft.value === 0 &&
        confirmation.value.trim().toLowerCase() === confirmPhrase.toLowerCase(),
);

const submitLabel = computed(() =>
    secondsLeft.value > 0
        ? t('deleteUser.waiting', { count: secondsLeft.value })
        : t('deleteUser.title'),
);

function onOpenChange(next: boolean): void {
    open.value = next;

    clearInterval(timer);

    if (next) {
        confirmation.value = '';
        secondsLeft.value = CONFIRMATION_SECONDS;

        timer = setInterval(() => {
            if (secondsLeft.value > 0) {
                secondsLeft.value -= 1;
            } else {
                clearInterval(timer);
            }
        }, 1000);
    }
}

onUnmounted(() => clearInterval(timer));
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('deleteUser.title')"
            :description="t('deleteUser.description')"
        />
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">{{ t('deleteUser.warning') }}</p>
                <p class="text-sm">
                    {{ t('deleteUser.warningText') }}
                </p>
            </div>
            <Dialog :open="open" @update:open="onOpenChange">
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        data-test="delete-user-button"
                        >{{ t('deleteUser.title') }}</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>{{
                                t('deleteUser.confirmTitle')
                            }}</DialogTitle>
                            <DialogDescription>
                                {{ t('deleteUser.confirmText') }}
                            </DialogDescription>
                            <p
                                class="text-sm font-medium text-red-600 dark:text-red-100"
                            >
                                {{ t('deleteUser.irreversible') }}
                            </p>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="confirmation">
                                {{
                                    t('deleteUser.confirmPhraseLabel', {
                                        phrase: confirmPhrase,
                                    })
                                }}
                            </Label>
                            <Input
                                id="confirmation"
                                v-model="confirmation"
                                name="confirmation"
                                autocomplete="off"
                                spellcheck="false"
                                :placeholder="confirmPhrase"
                            />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">{{
                                t('deleteUser.password')
                            }}</Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                :placeholder="t('deleteUser.password')"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    {{ t('common.cancel') }}
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="processing || !canSubmit"
                                data-test="confirm-delete-user-button"
                            >
                                {{ submitLabel }}
                            </Button>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
