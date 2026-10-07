<script setup lang="ts">
import { Form, Head, setLayoutProps, usePage } from '@inertiajs/vue3';
import { BadgeCheck, BadgeX, Link2 } from '@lucide/vue';
import { computed, watchEffect } from 'vue';
import LinkedAccountController from '@/actions/App/Http/Controllers/Settings/LinkedAccountController';
import LinkedAccountOAuthController from '@/actions/App/Http/Controllers/Settings/LinkedAccountOAuthController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useLocale } from '@/composables/useLocale';
import { edit } from '@/routes/accounts';
import { redirect as redirectToProvider } from '@/routes/linked';

const page = usePage();
const user = computed(() => page.props.auth.user);
const isVerified = computed(() =>
    Boolean(user.value.hytale_account_verified_at),
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

        <Form
            v-bind="LinkedAccountController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardContent class="space-y-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">Hytale</p>
                            <p class="text-muted-foreground text-sm">
                                {{ t('settings.accounts.hytale.description') }}
                            </p>
                        </div>
                        <Badge v-if="isVerified" variant="secondary">
                            <BadgeCheck />
                            {{ t('settings.accounts.verified') }}
                        </Badge>
                        <Badge v-else variant="outline">
                            <BadgeX />
                            {{ t('settings.accounts.notVerified') }}
                        </Badge>
                    </div>

                    <p v-if="!isVerified" class="text-muted-foreground text-sm">
                        {{ t('settings.accounts.hytale.unverifiedHint') }}
                    </p>
                    <p v-else class="text-muted-foreground text-sm">
                        {{ t('settings.accounts.hytale.verifiedHint') }}
                    </p>

                    <div class="grid gap-2">
                        <Label for="hytale_nickname">{{
                            t('settings.accounts.hytale.nickname')
                        }}</Label>
                        <Input
                            id="hytale_nickname"
                            class="mt-1 block w-full"
                            name="hytale_nickname"
                            :default-value="user.hytale_nickname ?? ''"
                            :disabled="isVerified"
                            :placeholder="
                                t(
                                    'settings.accounts.hytale.nicknamePlaceholder',
                                )
                            "
                        />
                        <InputError
                            class="mt-2"
                            :message="errors.hytale_nickname"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="hytale_id">{{
                            t('settings.accounts.hytale.id')
                        }}</Label>
                        <Input
                            id="hytale_id"
                            class="mt-1 block w-full"
                            name="hytale_id"
                            :default-value="user.hytale_id ?? ''"
                            :disabled="isVerified"
                            :placeholder="
                                t('settings.accounts.hytale.idPlaceholder')
                            "
                        />
                        <InputError class="mt-2" :message="errors.hytale_id" />
                    </div>

                    <Button
                        v-if="!isVerified"
                        :disabled="processing"
                        data-test="update-accounts-button"
                    >
                        {{ t('common.save') }}
                    </Button>
                </CardContent>
            </Card>
        </Form>

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
