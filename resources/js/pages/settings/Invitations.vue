<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import InvitationController from '@/actions/App/Http/Controllers/Settings/InvitationController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/hytale';
import { edit as editInvitations } from '@/routes/invitations';

type Invitation = {
    id: string;
    email: string;
    status: 'pending' | 'accepted' | 'revoked' | 'expired';
    created_at: string | null;
    expires_at: string;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Invitations',
                href: editInvitations(),
            },
        ],
    },
});

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
</script>

<template>
    <Head title="Invitations" />

    <h1 class="sr-only">Invitations</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Invitations"
            description="Invite external guests to create an account"
        />

        <p class="text-muted-foreground text-sm">
            {{ activeCount }} of {{ limit }} active invitations. School email
            addresses register on their own and cannot be invited. Pending
            invitations expire after 7 days.
        </p>

        <Form
            v-bind="InvitationController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="email">Guest email</Label>
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
                <Button :disabled="processing" data-test="invite-button"
                    >Invite</Button
                >
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
                        Invited {{ formatDateTime(invitation.created_at) }}
                        <template v-if="invitation.status === 'pending'">
                            · expires
                            {{ formatDateTime(invitation.expires_at) }}
                        </template>
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <Badge
                        v-if="invitation.status === 'accepted'"
                        variant="secondary"
                    >
                        Accepted
                    </Badge>
                    <Badge
                        v-else-if="invitation.status === 'pending'"
                        variant="outline"
                    >
                        Pending
                    </Badge>
                    <Badge
                        v-else-if="invitation.status === 'revoked'"
                        variant="outline"
                    >
                        Revoked
                    </Badge>
                    <Badge v-else variant="outline"> Expired </Badge>

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
                            Revoke
                        </Button>
                    </Form>
                </div>
            </div>
        </div>

        <p v-else class="text-muted-foreground text-sm">
            You have not invited anyone yet.
        </p>
    </div>
</template>
