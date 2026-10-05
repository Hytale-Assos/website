<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatDateTime } from '@/lib/hytale';
import { edit as editData } from '@/routes/data';

type PasskeyEntry = {
    name: string;
    created_at: string | null;
    last_used_at: string | null;
};

type SessionEntry = {
    ip_address: string | null;
    user_agent: string | null;
    last_activity: string | null;
};

defineProps<{
    sections: {
        account: {
            name: string;
            email: string;
            created_at: string | null;
        };
        identification: {
            firstname: string | null;
            lastname: string | null;
            grade_level: string | null;
            status: string | null;
            is_public: boolean;
        };
        linked_accounts: {
            discord: { linked: boolean; nickname: string | null };
            hytale: {
                linked: boolean;
                nickname: string | null;
                verified_at: string | null;
            };
        };
        security: {
            two_factor: { enabled: boolean; confirmed_at: string | null };
            passkeys: PasskeyEntry[];
            sessions: SessionEntry[];
        };
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Data & privacy',
                href: editData(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Data & privacy" />

    <h1 class="sr-only">Data &amp; privacy</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Data & privacy"
            description="Everything the website stores about you"
        />

        <Alert>
            <Lock class="size-4" />
            <AlertTitle>
                All data that can identify you is private and encrypted
            </AlertTitle>
            <AlertDescription>
                Your name, email address, and identification details are stored
                encrypted. Only you can see this page. Data held by the game
                core API is not included.
            </AlertDescription>
        </Alert>

        <Card>
            <CardHeader>
                <CardTitle>Account</CardTitle>
                <CardDescription>Your account details</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">Name</span>
                    <span class="text-sm">{{ sections.account.name }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">Email</span>
                    <span class="text-sm">{{ sections.account.email }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm"
                        >Member since</span
                    >
                    <span class="text-sm">{{
                        formatDateTime(sections.account.created_at)
                    }}</span>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Identification</CardTitle>
                <CardDescription>
                    Your identification data, encrypted at rest
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm"
                        >First name</span
                    >
                    <span class="text-sm">{{
                        sections.identification.firstname ?? '—'
                    }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">Last name</span>
                    <span class="text-sm">{{
                        sections.identification.lastname ?? '—'
                    }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm"
                        >Grade level</span
                    >
                    <span class="text-sm">{{
                        sections.identification.grade_level ?? '—'
                    }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">Status</span>
                    <Badge
                        v-if="sections.identification.status"
                        variant="secondary"
                        class="capitalize"
                    >
                        {{ sections.identification.status }}
                    </Badge>
                    <span v-else class="text-sm">—</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm"
                        >Public leaderboard</span
                    >
                    <span class="text-sm">{{
                        sections.identification.is_public ? 'Yes' : 'No'
                    }}</span>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Linked accounts</CardTitle>
                <CardDescription>
                    Third-party accounts linked to your profile
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">Discord</span>
                    <span class="text-sm">{{
                        sections.linked_accounts.discord.linked
                            ? (sections.linked_accounts.discord.nickname ??
                              'Linked')
                            : 'Not linked'
                    }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">Hytale</span>
                    <span class="text-sm">{{
                        sections.linked_accounts.hytale.linked
                            ? (sections.linked_accounts.hytale.nickname ??
                              'Linked')
                            : 'Not linked'
                    }}</span>
                </div>
                <div
                    v-if="sections.linked_accounts.hytale.verified_at"
                    class="flex justify-between gap-4"
                >
                    <span class="text-muted-foreground text-sm"
                        >Hytale account verified</span
                    >
                    <span class="text-sm">{{
                        formatDateTime(
                            sections.linked_accounts.hytale.verified_at,
                        )
                    }}</span>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Security</CardTitle>
                <CardDescription>
                    Passkeys, two-factor authentication and active sessions
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <div class="space-y-4">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground text-sm"
                            >Two-factor authentication</span
                        >
                        <Badge
                            v-if="sections.security.two_factor.enabled"
                            variant="secondary"
                        >
                            Enabled
                        </Badge>
                        <Badge v-else variant="outline"> Disabled </Badge>
                    </div>
                    <div
                        v-if="sections.security.two_factor.confirmed_at"
                        class="flex justify-between gap-4"
                    >
                        <span class="text-muted-foreground text-sm"
                            >Enabled since</span
                        >
                        <span class="text-sm">{{
                            formatDateTime(
                                sections.security.two_factor.confirmed_at,
                            )
                        }}</span>
                    </div>
                </div>

                <Separator />

                <div class="space-y-4">
                    <p class="text-sm font-medium">
                        Passkeys ({{ sections.security.passkeys.length }})
                    </p>
                    <p
                        v-if="sections.security.passkeys.length === 0"
                        class="text-muted-foreground text-sm"
                    >
                        No passkeys registered.
                    </p>
                    <div
                        v-for="passkey in sections.security.passkeys"
                        :key="passkey.name"
                        class="flex justify-between gap-4"
                    >
                        <span class="text-sm">{{ passkey.name }}</span>
                        <span class="text-muted-foreground text-sm">
                            Added {{ formatDateTime(passkey.created_at) }}, last
                            used
                            {{ formatDateTime(passkey.last_used_at) }}
                        </span>
                    </div>
                </div>

                <Separator />

                <div class="space-y-4">
                    <p class="text-sm font-medium">
                        Active sessions ({{
                            sections.security.sessions.length
                        }})
                    </p>
                    <p
                        v-if="sections.security.sessions.length === 0"
                        class="text-muted-foreground text-sm"
                    >
                        No active sessions.
                    </p>
                    <div
                        v-for="(session, index) in sections.security.sessions"
                        :key="index"
                        class="flex justify-between gap-4"
                    >
                        <span class="text-sm">{{
                            session.ip_address ?? '—'
                        }}</span>
                        <span class="text-muted-foreground text-sm">
                            {{ session.user_agent ?? 'Unknown device' }} —
                            {{ formatDateTime(session.last_activity) }}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
