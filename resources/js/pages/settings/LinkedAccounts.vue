<script setup lang="ts">
import { Form, Head, setLayoutProps, usePage } from '@inertiajs/vue3';
import { BadgeCheck, BadgeX, Link2, ShieldAlert } from '@lucide/vue';
import { computed, watchEffect } from 'vue';
import LinkedAccountOAuthController from '@/actions/App/Http/Controllers/Settings/LinkedAccountOAuthController';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import { edit } from '@/routes/accounts';
import { redirect as redirectToProvider } from '@/routes/linked';

const page = usePage();
const user = computed(() => page.props.auth.user);
const isHytaleLinked = computed(() => Boolean(user.value.hytale_id));
// Verification is internal: it only marks a Hytale-issued id (OAuth) as
// opposed to a hand-entered one, so it is never surfaced as a badge. A linked
// but unverified account is a legacy manual entry awaiting confirmation.
const isHytaleVerified = computed(() =>
    Boolean(user.value.hytale_account_verified_at),
);
const needsHytaleVerification = computed(
    () => isHytaleLinked.value && !isHytaleVerified.value,
);
const isDiscordLinked = computed(() => Boolean(user.value.discord_id));

const { t } = useLocale();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('settings.accounts.head'),
                href: edit(),
            },
        ],
    });
});
</script>

<template>
    <Head :title="t('settings.accounts.head')" />

    <h1 class="sr-only">{{ t('settings.accounts.head') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.accounts.title')"
            :description="t('settings.accounts.description')"
        />

        <Card>
            <CardContent class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium">Hytale</p>
                        <p class="text-muted-foreground text-sm">
                            {{ t('settings.accounts.hytale.description') }}
                        </p>
                    </div>
                    <Badge v-if="isHytaleLinked" variant="secondary">
                        <BadgeCheck />
                        {{ t('settings.accounts.linked') }}
                    </Badge>
                    <Badge v-else variant="outline">
                        <BadgeX />
                        {{ t('settings.accounts.notLinked') }}
                    </Badge>
                </div>

                <p v-if="isHytaleLinked" class="text-muted-foreground text-sm">
                    {{ t('settings.accounts.linkedAs') }}
                    <span class="text-foreground font-medium">{{
                        user.hytale_nickname
                    }}</span>
                </p>
                <p v-else class="text-muted-foreground text-sm">
                    {{ t('settings.accounts.hytale.hint') }}
                </p>

                <Alert v-if="needsHytaleVerification">
                    <ShieldAlert class="size-4" />
                    <AlertTitle>
                        {{ t('settings.accounts.hytale.verifyTitle') }}
                    </AlertTitle>
                    <AlertDescription>
                        {{ t('settings.accounts.hytale.verifyDescription') }}
                    </AlertDescription>
                </Alert>

                <div class="flex items-center gap-2">
                    <Button
                        v-if="!isHytaleLinked"
                        as-child
                        variant="outline"
                        data-test="link-hytale-button"
                    >
                        <a :href="redirectToProvider.url('hytale')">
                            <Link2 />
                            {{ t('settings.accounts.link') }}
                        </a>
                    </Button>

                    <template v-else>
                        <Button
                            v-if="needsHytaleVerification"
                            as-child
                            variant="outline"
                            data-test="verify-hytale-button"
                        >
                            <a :href="redirectToProvider.url('hytale')">
                                <Link2 />
                                {{ t('settings.accounts.hytale.verify') }}
                            </a>
                        </Button>

                        <Form
                            v-bind="
                                LinkedAccountOAuthController.destroy.form(
                                    'hytale',
                                )
                            "
                            v-slot="{ processing }"
                        >
                            <Button
                                variant="outline"
                                :disabled="processing"
                                data-test="unlink-hytale-button"
                            >
                                {{ t('settings.accounts.unlink') }}
                            </Button>
                        </Form>
                    </template>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardContent class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium">Discord</p>
                        <p class="text-muted-foreground text-sm">
                            {{ t('settings.accounts.discord.description') }}
                        </p>
                    </div>
                    <Badge v-if="isDiscordLinked" variant="secondary">
                        <BadgeCheck />
                        {{ t('settings.accounts.linked') }}
                    </Badge>
                    <Badge v-else variant="outline">
                        <BadgeX />
                        {{ t('settings.accounts.notLinked') }}
                    </Badge>
                </div>

                <p v-if="isDiscordLinked" class="text-muted-foreground text-sm">
                    {{ t('settings.accounts.linkedAs') }}
                    <span class="text-foreground font-medium">{{
                        user.discord_nickname
                    }}</span>
                </p>
                <p v-else class="text-muted-foreground text-sm">
                    {{ t('settings.accounts.discord.hint') }}
                </p>

                <div class="flex items-center justify-between">
                    <Button
                        v-if="!isDiscordLinked"
                        as-child
                        variant="outline"
                        data-test="link-discord-button"
                    >
                        <a :href="redirectToProvider.url('discord')">
                            <Link2 />
                            {{ t('settings.accounts.link') }}
                        </a>
                    </Button>

                    <Form
                        v-else
                        v-bind="
                            LinkedAccountOAuthController.destroy.form('discord')
                        "
                        v-slot="{ processing }"
                    >
                        <Button
                            variant="outline"
                            :disabled="processing"
                            data-test="unlink-discord-button"
                        >
                            {{ t('settings.accounts.unlink') }}
                        </Button>
                    </Form>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
