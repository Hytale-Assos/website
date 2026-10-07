<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import IdentityController from '@/actions/App/Http/Controllers/Settings/IdentityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useLocale } from '@/composables/useLocale';

defineProps<{
    gradeLevels: { value: string; label: string }[];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const isInternal = computed(() => Boolean(user.value?.is_internal));
const gradeLevel = ref<string>(user.value?.grade_level ?? '');
const { t } = useLocale();
</script>

<template>
    <Head :title="t('settings.identity.head')" />

    <h1 class="sr-only">{{ t('settings.identity.head') }}</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="t('settings.identity.title')"
                :description="t('settings.identity.description')"
            />
            <Badge :variant="isInternal ? 'secondary' : 'outline'">
                {{
                    isInternal
                        ? t('settings.identity.internal')
                        : t('settings.identity.external')
                }}
            </Badge>
        </div>

        <p class="text-muted-foreground text-sm">
            {{ t('settings.identity.statusHint') }}
        </p>

        <Form
            v-bind="IdentityController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="firstname">{{
                    t('settings.identity.firstname')
                }}</Label>
                <Input
                    id="firstname"
                    class="mt-1 block w-full"
                    name="firstname"
                    :default-value="user.firstname ?? ''"
                    :required="isInternal"
                    autocomplete="given-name"
                    :placeholder="t('settings.identity.firstnamePlaceholder')"
                />
                <InputError class="mt-2" :message="errors.firstname" />
            </div>

            <div class="grid gap-2">
                <Label for="lastname">{{
                    t('settings.identity.lastname')
                }}</Label>
                <Input
                    id="lastname"
                    class="mt-1 block w-full"
                    name="lastname"
                    :default-value="user.lastname ?? ''"
                    :required="isInternal"
                    autocomplete="family-name"
                    :placeholder="t('settings.identity.lastnamePlaceholder')"
                />
                <InputError class="mt-2" :message="errors.lastname" />
            </div>

            <div v-if="isInternal" class="grid gap-2">
                <Label for="school_email">{{
                    t('settings.identity.schoolEmail')
                }}</Label>
                <Input
                    id="school_email"
                    type="email"
                    class="mt-1 block w-full"
                    name="school_email"
                    :default-value="user.school_email ?? ''"
                    required
                    autocomplete="off"
                    :placeholder="t('settings.identity.schoolEmailPlaceholder')"
                />
                <p class="text-muted-foreground text-sm">
                    {{ t('settings.identity.schoolEmailHint') }}
                </p>
                <InputError class="mt-2" :message="errors.school_email" />
            </div>

            <div v-if="isInternal" class="grid gap-2">
                <Label for="grade_level">{{
                    t('settings.identity.grade')
                }}</Label>
                <input type="hidden" name="grade_level" :value="gradeLevel" />
                <Select v-model="gradeLevel">
                    <SelectTrigger id="grade_level" class="mt-1 w-full">
                        <SelectValue
                            :placeholder="
                                t('settings.identity.gradePlaceholder')
                            "
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="level in gradeLevels"
                            :key="level.value"
                            :value="level.value"
                        >
                            {{ level.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError class="mt-2" :message="errors.grade_level" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-identity-button"
                    >{{ t('common.save') }}</Button
                >
            </div>
        </Form>
    </div>
</template>
