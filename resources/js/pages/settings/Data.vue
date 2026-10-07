<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { Download, Lock } from '@lucide/vue';
import { watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { useLocale } from '@/composables/useLocale';
import { formatDateTime } from '@/lib/hytale';
import { edit as editData, exportMethod as exportData } from '@/routes/data';

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
            school_email: string | null;
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

const { t } = useLocale();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('settings.data.head'),
                href: editData(),
            },
        ],
    });
});
</script>

<template>
    <Head :title="t('settings.data.head')" />

    <h1 class="sr-only">{{ t('settings.data.head') }}</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="t('settings.data.title')"
            :description="t('settings.data.description')"
        />

        <Alert>
            <Lock class="size-4" />
            <AlertTitle>
                {{ t('settings.data.alert.title') }}
            </AlertTitle>
            <AlertDescription>
                {{ t('settings.data.alert.description') }}
            </AlertDescription>
        </Alert>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('settings.data.account') }}</CardTitle>
                <CardDescription>{{
                    t('settings.data.account.desc')
                }}</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">{{
                        t('settings.data.name')
                    }}</span>
                    <span class="text-sm">{{ sections.account.name }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">{{
                        t('settings.data.email')
                    }}</span>
                    <span class="text-sm">{{ sections.account.email }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">{{
                        t('settings.data.schoolEmail')
                    }}</span>
                    <span class="text-sm">{{
                        sections.account.school_email ?? '—'
                    }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">{{
                        t('settings.data.memberSince')
                    }}</span>
                    <span class="text-sm">{{
                        formatDateTime(sections.account.created_at)
                    }}</span>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('settings.data.identification') }}</CardTitle>
                <CardDescription>
                    {{ t('settings.data.identification.desc') }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">{{
                        t('settings.data.firstname')
                    }}</span>
                    <span class="text-sm">{{
                        sections.identification.firstname ?? '—'
                    }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">{{
                        t('settings.data.lastname')
                    }}</span>
                    <span class="text-sm">{{
                        sections.identification.lastname ?? '—'
                    }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">{{
                        t('settings.data.grade')
                    }}</span>
                    <span class="text-sm">{{
                        sections.identification.grade_level ?? '—'
                    }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">{{
                        t('settings.data.status')
                    }}</span>
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
                    <span class="text-muted-foreground text-sm">{{
                        t('settings.data.public')
                    }}</span>
                    <span class="text-sm">{{
                        sections.identification.is_public
                            ? t('common.yes')
                            : t('common.no')
                    }}</span>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('settings.data.linked') }}</CardTitle>
                <CardDescription>
                    {{ t('settings.data.linked.desc') }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">Discord</span>
                    <span class="text-sm">{{
                        sections.linked_accounts.discord.linked
                            ? (sections.linked_accounts.discord.nickname ??
                              t('settings.data.linkedLabel'))
                            : t('settings.data.notLinked')
                    }}</span>
                </div>
                <div class="flex justify-between gap-4">
                    <span class="text-muted-foreground text-sm">Hytale</span>
                    <span class="text-sm">{{
                        sections.linked_accounts.hytale.linked
                            ? (sections.linked_accounts.hytale.nickname ??
                              t('settings.data.linkedLabel'))
                            : t('settings.data.notLinked')
                    }}</span>
                </div>
                <div
                    v-if="sections.linked_accounts.hytale.verified_at"
                    class="flex justify-between gap-4"
                >
                    <span class="text-muted-foreground text-sm">{{
                        t('settings.data.hytaleVerified')
                    }}</span>
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
                <CardTitle>{{ t('settings.data.security') }}</CardTitle>
                <CardDescription>
                    {{ t('settings.data.security.desc') }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <div class="space-y-4">
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground text-sm">{{
                            t('settings.data.twoFactor')
                        }}</span>
                        <Badge
                            v-if="sections.security.two_factor.enabled"
                            variant="secondary"
                        >
                            {{ t('settings.data.enabled') }}
                        </Badge>
                        <Badge v-else variant="outline">
                            {{ t('settings.data.disabled') }}
                        </Badge>
                    </div>
                    <div
                        v-if="sections.security.two_factor.confirmed_at"
                        class="flex justify-between gap-4"
                    >
                        <span class="text-muted-foreground text-sm">{{
                            t('settings.data.enabledSince')
                        }}</span>
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
                        {{
                            t('settings.data.passkeys', {
                                count: sections.security.passkeys.length,
                            })
                        }}
                    </p>
                    <p
                        v-if="sections.security.passkeys.length === 0"
                        class="text-muted-foreground text-sm"
                    >
                        {{ t('settings.data.noPasskeys') }}
                    </p>
                    <div
                        v-for="passkey in sections.security.passkeys"
                        :key="passkey.name"
                        class="flex justify-between gap-4"
                    >
                        <span class="text-sm">{{ passkey.name }}</span>
                        <span class="text-muted-foreground text-sm">
                            {{
                                t('settings.data.passkeyAdded', {
                                    date: formatDateTime(passkey.created_at),
                                    lastUsed: formatDateTime(
                                        passkey.last_used_at,
                                    ),
                                })
                            }}
                        </span>
                    </div>
                </div>

                <Separator />

                <div class="space-y-4">
                    <p class="text-sm font-medium">
                        {{
                            t('settings.data.sessions', {
                                count: sections.security.sessions.length,
                            })
                        }}
                    </p>
                    <p
                        v-if="sections.security.sessions.length === 0"
                        class="text-muted-foreground text-sm"
                    >
                        {{ t('settings.data.noSessions') }}
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
                            {{
                                session.user_agent ??
                                t('settings.data.unknownDevice')
                            }}
                            — {{ formatDateTime(session.last_activity) }}
                        </span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="flex justify-end">
            <Button as-child variant="outline" data-test="export-data-button">
                <a :href="exportData.url()" download>
                    <Download class="size-4" />
                    {{ t('settings.data.export') }}
                </a>
            </Button>
        </div>
    </div>
</template>
