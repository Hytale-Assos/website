<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import IdentityController from '@/actions/App/Http/Controllers/Settings/IdentityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { edit as editIdentity } from '@/routes/identity';

defineProps<{
    gradeLevels: { value: string; label: string }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Identity settings',
                href: editIdentity(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const gradeLevel = ref<string>(user.value?.grade_level ?? '');
</script>

<template>
    <Head title="Identity settings" />

    <h1 class="sr-only">Identity settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Identity"
            description="Your identification data, private and encrypted"
        />

        <Form
            v-bind="IdentityController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="firstname">First name</Label>
                <Input
                    id="firstname"
                    class="mt-1 block w-full"
                    name="firstname"
                    :default-value="user.firstname ?? ''"
                    required
                    autocomplete="given-name"
                    placeholder="First name"
                />
                <InputError class="mt-2" :message="errors.firstname" />
            </div>

            <div class="grid gap-2">
                <Label for="lastname">Last name</Label>
                <Input
                    id="lastname"
                    class="mt-1 block w-full"
                    name="lastname"
                    :default-value="user.lastname ?? ''"
                    required
                    autocomplete="family-name"
                    placeholder="Last name"
                />
                <InputError class="mt-2" :message="errors.lastname" />
            </div>

            <div class="grid gap-2">
                <Label for="grade_level">Grade level</Label>
                <input type="hidden" name="grade_level" :value="gradeLevel" />
                <Select v-model="gradeLevel">
                    <SelectTrigger id="grade_level" class="mt-1 w-full">
                        <SelectValue placeholder="Select your grade level" />
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

            <div class="grid gap-2">
                <Label>Status</Label>
                <div class="mt-1 flex flex-col gap-2">
                    <label
                        class="flex items-center gap-2 text-sm"
                        for="status-internal"
                    >
                        <input
                            id="status-internal"
                            type="radio"
                            name="status"
                            value="internal"
                            :checked="user.is_internal"
                            required
                        />
                        Internal
                    </label>
                    <label
                        class="flex items-center gap-2 text-sm"
                        for="status-external"
                    >
                        <input
                            id="status-external"
                            type="radio"
                            name="status"
                            value="external"
                            :checked="user.is_external"
                            required
                        />
                        External
                    </label>
                </div>
                <InputError class="mt-2" :message="errors.status" />
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-identity-button"
                    >Save</Button
                >
            </div>
        </Form>
    </div>
</template>
