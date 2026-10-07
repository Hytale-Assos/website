<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import InvitationController from '@/actions/App/Http/Controllers/Settings/InvitationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/composables/useLocale';
import { formatDateTime } from '@/lib/hytale';
import { edit as editInvitations } from '@/routes/invitations';

type Invitation = {
    id: string;
    email: string;
    status: 'pending' | 'accepted' | 'revoked' | 'expired';
    created_at: string | null;
    expires_at: string;
};

const props = defineProps<{
    invitations: Invitation[];
    limit: number;
}>();

const activeCount = computed(
    () =>
        props.invitations.filter(
            (invitation) =>
                invitation.status === 'accepted' ||
                invitation.status === 'pending',
        ).length,
);

const { t } = useLocale();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('settings.invitations.title'),
                href: editInvitations(),
            },
        ],
    });
});
</script>

<template>
    <Head :title="t('settings.invitations.title')" />

    <h1 class="sr-only">{{ t('settings.invitations.title') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="t('settings.invitations.title')"
            :description="t('settings.invitations.description')"
        />

        <p class="text-muted-foreground text-sm">
            {{
                t('settings.invitations.summary', {
                    active: activeCount,
                    limit,
                })
            }}
        </p>

        <Form
            v-bind="InvitationController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="email">{{ t('settings.invitations.email') }}</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    required
                    autocomplete="off"
                    placeholder="guest@example.com"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="invite-button">{{
                    t('settings.invitations.invite')
                }}</Button>
            </div>
        </Form>

        <div v-if="invitations.length > 0" class="space-y-4">
            <div
                v-for="invitation in invitations"
                :key="invitation.id"
                class="flex items-center justify-between gap-4"
            >
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium">
                        {{ invitation.email }}
                    </p>
                    <p class="text-muted-foreground text-sm">
                        {{
                            t('settings.invitations.invited', {
                                date: formatDateTime(invitation.created_at),
                            })
                        }}
                        <template v-if="invitation.status === 'pending'">
                            {{
                                t('settings.invitations.expires', {
                                    date: formatDateTime(invitation.expires_at),
                                })
                            }}
                        </template>
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <Badge
                        v-if="invitation.status === 'accepted'"
                        variant="secondary"
                    >
                        {{ t('settings.invitations.status.accepted') }}
                    </Badge>
                    <Badge
                        v-else-if="invitation.status === 'pending'"
                        variant="outline"
                    >
                        {{ t('settings.invitations.status.pending') }}
                    </Badge>
                    <Badge
                        v-else-if="invitation.status === 'revoked'"
                        variant="outline"
                    >
                        {{ t('settings.invitations.status.revoked') }}
                    </Badge>
                    <Badge v-else variant="outline">
                        {{ t('settings.invitations.status.expired') }}
                    </Badge>

                    <Form
                        v-if="invitation.status === 'pending'"
                        v-bind="
                            InvitationController.destroy.form({
                                invitation: invitation.id,
                            })
                        "
                        v-slot="{ processing: revoking }"
                    >
                        <Button
                            variant="ghost"
                            size="sm"
                            :disabled="revoking"
                            data-test="revoke-invitation-button"
                        >
                            {{ t('settings.invitations.revoke') }}
                        </Button>
                    </Form>
                </div>
            </div>
        </div>

        <p v-else class="text-muted-foreground text-sm">
            {{ t('settings.invitations.empty') }}
        </p>
    </div>
</template>
