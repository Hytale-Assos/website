<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { BadgeCheck, BadgeX, Link2 } from '@lucide/vue';
import { computed } from 'vue';
import LinkedAccountController from '@/actions/App/Http/Controllers/Settings/LinkedAccountController';
import LinkedAccountOAuthController from '@/actions/App/Http/Controllers/Settings/LinkedAccountOAuthController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/accounts';
import { redirect as redirectToProvider } from '@/routes/linked';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Linked accounts settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const isVerified = computed(() =>
    Boolean(user.value.hytale_account_verified_at),
);
const isDiscordLinked = computed(() => Boolean(user.value.discord_id));
</script>

<template>
    <Head title="Linked accounts settings" />

    <h1 class="sr-only">Linked accounts settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Linked accounts"
            description="Link your game accounts to your profile"
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
                                Your Hytale identity, used for servers,
                                whitelist and points
                            </p>
                        </div>
                        <Badge v-if="isVerified" variant="secondary">
                            <BadgeCheck />
                            Verified
                        </Badge>
                        <Badge v-else variant="outline">
                            <BadgeX />
                            Not verified
                        </Badge>
                    </div>

                    <p v-if="!isVerified" class="text-muted-foreground text-sm">
                        The Hytale account verification is not available yet.
                        Enter your details yourself: they will be reviewed by
                        the team and confirmed later through Hytale OAuth.
                    </p>
                    <p v-else class="text-muted-foreground text-sm">
                        Your Hytale account has been confirmed through Hytale
                        OAuth. These details are provided by Hytale and cannot
                        be changed here.
                    </p>

                    <div class="grid gap-2">
                        <Label for="hytale_nickname">Hytale nickname</Label>
                        <Input
                            id="hytale_nickname"
                            class="mt-1 block w-full"
                            name="hytale_nickname"
                            :default-value="user.hytale_nickname ?? ''"
                            :disabled="isVerified"
                            placeholder="Your in-game nickname"
                        />
                        <InputError
                            class="mt-2"
                            :message="errors.hytale_nickname"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="hytale_id">Hytale ID</Label>
                        <Input
                            id="hytale_id"
                            class="mt-1 block w-full"
                            name="hytale_id"
                            :default-value="user.hytale_id ?? ''"
                            :disabled="isVerified"
                            placeholder="Your Hytale account UUID"
                        />
                        <InputError class="mt-2" :message="errors.hytale_id" />
                    </div>

                    <Button
                        v-if="!isVerified"
                        :disabled="processing"
                        data-test="update-accounts-button"
                    >
                        Save
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
                            Link your Discord account to your profile
                        </p>
                    </div>
                    <Badge v-if="isDiscordLinked" variant="secondary">
                        <BadgeCheck />
                        Linked
                    </Badge>
                    <Badge v-else variant="outline">
                        <BadgeX />
                        Not linked
                    </Badge>
                </div>

                <p v-if="isDiscordLinked" class="text-muted-foreground text-sm">
                    Linked as
                    <span class="text-foreground font-medium">{{
                        user.discord_nickname
                    }}</span>
                </p>
                <p v-else class="text-muted-foreground text-sm">
                    Your Discord identity is provided by Discord OAuth.
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
                            Link
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
                            Unlink
                        </Button>
                    </Form>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
