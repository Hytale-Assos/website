<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Boxes,
    CalendarDays,
    Check,
    Code,
    Gamepad2,
    Globe,
    GraduationCap,
    Menu,
    MessageCircle,
    Moon,
    Sparkles,
    Sun,
    Trophy,
    UserCheck,
    UserPlus,
    Wrench,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Locale } from '@/lang';
import { localeLabels } from '@/lang';
import { useLocale } from '@/composables/useLocale';
import { useAppearance } from '@/composables/useAppearance';
import { dashboard, login, register } from '@/routes';

const DISCORD_URL = 'https://discord.gg/hytale-assos';

const { t, locale, setLocale } = useLocale();
const { resolvedAppearance, updateAppearance } = useAppearance();

const otherLocale = computed<Locale>(() =>
    locale.value === 'fr' ? 'en' : 'fr',
);

const menuOpen = ref(false);

const toggleAppearance = () => {
    updateAppearance(resolvedAppearance.value === 'dark' ? 'light' : 'dark');
};

const navItems = [
    { key: 'nav.about', href: '#about' },
    { key: 'nav.offer', href: '#offer' },
    { key: 'nav.event', href: '#event' },
    { key: 'nav.join', href: '#join' },
] as const;

const stats = [
    { value: '60+', key: 'hero.stat1' },
    { value: '2', key: 'hero.stat2' },
    { value: '10+', key: 'hero.stat3' },
    { value: '5+', key: 'hero.stat4' },
] as const;

const offers = [
    {
        icon: Gamepad2,
        title: 'offer.vanilla.title',
        desc: 'offer.vanilla.desc',
    },
    { icon: Wrench, title: 'offer.modded.title', desc: 'offer.modded.desc' },
    { icon: Code, title: 'offer.java.title', desc: 'offer.java.desc' },
    { icon: Trophy, title: 'offer.events.title', desc: 'offer.events.desc' },
] as const;

const steps = [
    { icon: UserPlus, title: 'join.step1.title', desc: 'join.step1.desc' },
    { icon: UserCheck, title: 'join.step2.title', desc: 'join.step2.desc' },
    {
        icon: MessageCircle,
        title: 'join.step3.title',
        desc: 'join.step3.desc',
    },
] as const;
</script>

<template>
    <Head :title="t('meta.title')">
        <meta name="description" :content="t('meta.description')" />
    </Head>

    <div
        class="bg-background text-foreground relative min-h-screen overflow-x-hidden"
    >
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-[640px] bg-gradient-to-b from-emerald-500/10 via-cyan-500/5 to-transparent"
        ></div>

        <header
            class="border-border/60 bg-background/80 sticky top-0 z-40 border-b backdrop-blur-md"
        >
            <div
                class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-4 sm:px-6"
            >
                <a href="#top" class="flex items-center gap-2.5">
                    <span
                        class="flex size-9 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-cyan-600 text-white shadow-sm"
                    >
                        <Boxes class="size-5" />
                    </span>
                    <span class="text-base font-bold tracking-tight"
                        >Hytale Assos</span
                    >
                </a>

                <nav class="hidden items-center gap-8 md:flex">
                    <a
                        v-for="item in navItems"
                        :key="item.key"
                        :href="item.href"
                        class="text-muted-foreground hover:text-foreground text-sm font-medium transition-colors"
                    >
                        {{ t(item.key) }}
                    </a>
                </nav>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="border-border hover:bg-accent hover:text-accent-foreground inline-flex size-9 items-center justify-center rounded-md border transition-colors"
                        :aria-label="t('nav.discord')"
                        @click="toggleAppearance"
                    >
                        <Moon
                            v-if="resolvedAppearance === 'dark'"
                            class="size-4"
                        />
                        <Sun v-else class="size-4" />
                    </button>

                    <button
                        type="button"
                        class="border-border hover:bg-accent hover:text-accent-foreground inline-flex h-9 items-center gap-1.5 rounded-md border px-3 text-sm font-medium transition-colors"
                        @click="setLocale(otherLocale)"
                    >
                        <Globe class="size-4" />
                        {{ localeLabels[otherLocale] }}
                    </button>

                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="hidden sm:block"
                    >
                        <Button size="sm">
                            <Gamepad2 class="size-4" />
                            {{ t('nav.dashboard') }}
                        </Button>
                    </Link>
                    <template v-else>
                        <Link :href="login()" class="hidden sm:block">
                            <Button variant="ghost" size="sm">
                                {{ t('nav.login') }}
                            </Button>
                        </Link>
                        <Link :href="register()" class="hidden sm:block">
                            <Button size="sm">
                                {{ t('nav.register') }}
                            </Button>
                        </Link>
                    </template>

                    <button
                        type="button"
                        class="border-border hover:bg-accent inline-flex size-9 items-center justify-center rounded-md border md:hidden"
                        :aria-label="t('nav.about')"
                        @click="menuOpen = !menuOpen"
                    >
                        <X v-if="menuOpen" class="size-4" />
                        <Menu v-else class="size-4" />
                    </button>
                </div>
            </div>

            <nav
                v-if="menuOpen"
                class="border-border bg-background space-y-1 border-t px-4 py-3 md:hidden"
            >
                <a
                    v-for="item in navItems"
                    :key="item.key"
                    :href="item.href"
                    class="hover:bg-accent block rounded-md px-3 py-2 text-sm font-medium"
                    @click="menuOpen = false"
                >
                    {{ t(item.key) }}
                </a>
                <a
                    :href="DISCORD_URL"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="hover:bg-accent block rounded-md px-3 py-2 text-sm font-medium"
                >
                    {{ t('nav.discord') }}
                </a>
                <div class="flex gap-2 pt-2 sm:hidden">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="flex-1"
                    >
                        <Button class="w-full" size="sm">
                            {{ t('nav.dashboard') }}
                        </Button>
                    </Link>
                    <template v-else>
                        <Link :href="login()" class="flex-1">
                            <Button variant="outline" class="w-full" size="sm">
                                {{ t('nav.login') }}
                            </Button>
                        </Link>
                        <Link :href="register()" class="flex-1">
                            <Button class="w-full" size="sm">
                                {{ t('nav.register') }}
                            </Button>
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <main id="top">
            <section
                class="mx-auto grid w-full max-w-6xl gap-12 px-4 pt-16 pb-20 sm:px-6 lg:grid-cols-2 lg:items-center lg:pt-24"
            >
                <div>
                    <span
                        class="border-border bg-card text-muted-foreground inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-medium"
                    >
                        <Sparkles class="size-3.5 text-emerald-500" />
                        {{ t('hero.badge') }}
                    </span>

                    <h1
                        class="mt-6 text-4xl leading-[1.1] font-extrabold tracking-tight sm:text-5xl lg:text-6xl"
                    >
                        {{ t('hero.title') }}
                    </h1>

                    <p
                        class="text-muted-foreground mt-6 max-w-xl text-base leading-relaxed sm:text-lg"
                    >
                        {{ t('hero.subtitle') }}
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <Link v-if="!$page.props.auth.user" :href="register()">
                            <Button size="lg">
                                <UserPlus class="size-4" />
                                {{ t('hero.ctaPrimary') }}
                            </Button>
                        </Link>
                        <Link v-else :href="dashboard()">
                            <Button size="lg">
                                <Gamepad2 class="size-4" />
                                {{ t('nav.dashboard') }}
                            </Button>
                        </Link>
                        <a href="#offer">
                            <Button variant="outline" size="lg">
                                {{ t('hero.ctaSecondary') }}
                                <ArrowRight class="size-4" />
                            </Button>
                        </a>
                    </div>

                    <p class="text-muted-foreground mt-4 text-sm">
                        {{ t('hero.discordHint') }}
                        <a
                            :href="DISCORD_URL"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-foreground font-medium underline underline-offset-4"
                            >Discord</a
                        >.
                    </p>

                    <dl
                        class="mt-12 grid grid-cols-2 gap-6 border-t pt-8 sm:grid-cols-4 lg:border-none lg:pt-0"
                    >
                        <div v-for="stat in stats" :key="stat.key">
                            <dt
                                class="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                            >
                                {{ t(stat.key) }}
                            </dt>
                            <dd
                                class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl"
                            >
                                {{ stat.value }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="relative">
                    <div
                        class="from-card to-background relative overflow-hidden rounded-2xl border bg-gradient-to-br p-6 shadow-xl"
                    >
                        <div class="grid grid-cols-3 gap-3" aria-hidden="true">
                            <div
                                v-for="cell in 9"
                                :key="cell"
                                class="aspect-square rounded-lg border"
                                :class="
                                    cell % 3 === 1
                                        ? 'bg-gradient-to-br from-emerald-500/25 to-emerald-500/5'
                                        : cell % 3 === 0
                                          ? 'bg-gradient-to-br from-cyan-500/25 to-cyan-500/5'
                                          : 'bg-muted'
                                "
                            ></div>
                        </div>

                        <div
                            class="bg-card/95 absolute inset-x-6 bottom-6 flex items-center gap-3 rounded-xl border p-4 shadow-lg backdrop-blur"
                        >
                            <span
                                class="flex size-10 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-cyan-600 text-white"
                            >
                                <Code class="size-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">
                                    {{ t('offer.java.title') }}
                                </p>
                                <p
                                    class="text-muted-foreground truncate text-xs"
                                >
                                    Java · Hytale · from scratch
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="about" class="border-t">
                <div
                    class="mx-auto grid w-full max-w-6xl gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[1fr_1.2fr] lg:items-start"
                >
                    <div>
                        <h2
                            class="text-3xl font-bold tracking-tight sm:text-4xl"
                        >
                            {{ t('about.title') }}
                        </h2>
                        <p class="text-muted-foreground mt-4 text-lg">
                            {{ t('about.subtitle') }}
                        </p>
                    </div>
                    <div class="space-y-4">
                        <p class="leading-relaxed">{{ t('about.p1') }}</p>
                        <p class="leading-relaxed">{{ t('about.p2') }}</p>
                        <ul class="grid gap-3 pt-2 sm:grid-cols-2">
                            <li class="flex items-center gap-2 text-sm">
                                <Check class="size-4 text-emerald-500" />
                                {{ t('offer.vanilla.title') }}
                            </li>
                            <li class="flex items-center gap-2 text-sm">
                                <Check class="size-4 text-emerald-500" />
                                {{ t('offer.modded.title') }}
                            </li>
                            <li class="flex items-center gap-2 text-sm">
                                <Check class="size-4 text-emerald-500" />
                                {{ t('offer.java.title') }}
                            </li>
                            <li class="flex items-center gap-2 text-sm">
                                <Check class="size-4 text-emerald-500" />
                                {{ t('offer.events.title') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <section id="offer" class="bg-muted/30 border-t">
                <div class="mx-auto w-full max-w-6xl px-4 py-20 sm:px-6">
                    <div class="max-w-2xl">
                        <h2
                            class="text-3xl font-bold tracking-tight sm:text-4xl"
                        >
                            {{ t('offer.title') }}
                        </h2>
                        <p class="text-muted-foreground mt-4 text-lg">
                            {{ t('offer.subtitle') }}
                        </p>
                    </div>

                    <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <Card
                            v-for="offer in offers"
                            :key="offer.title"
                            class="transition-colors hover:border-emerald-500/40"
                        >
                            <CardContent class="space-y-4">
                                <span
                                    class="border-border bg-background flex size-11 items-center justify-center rounded-xl border"
                                >
                                    <component
                                        :is="offer.icon"
                                        class="size-5 text-emerald-500"
                                    />
                                </span>
                                <h3 class="font-semibold">
                                    {{ t(offer.title) }}
                                </h3>
                                <p
                                    class="text-muted-foreground text-sm leading-relaxed"
                                >
                                    {{ t(offer.desc) }}
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </section>

            <section id="event" class="border-t">
                <div class="mx-auto w-full max-w-6xl px-4 py-20 sm:px-6">
                    <div
                        class="from-card to-background relative overflow-hidden rounded-2xl border bg-gradient-to-br p-8 sm:p-12"
                    >
                        <div
                            class="pointer-events-none absolute -top-24 -right-24 size-64 rounded-full bg-emerald-500/10 blur-3xl"
                        ></div>
                        <div class="relative max-w-2xl">
                            <span
                                class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400"
                            >
                                <CalendarDays class="size-3.5" />
                                {{ t('event.tag') }}
                            </span>
                            <h2
                                class="mt-5 text-3xl font-bold tracking-tight sm:text-4xl"
                            >
                                {{ t('event.title') }}
                            </h2>
                            <p
                                class="text-muted-foreground mt-4 leading-relaxed"
                            >
                                {{ t('event.desc') }}
                            </p>

                            <div class="mt-6 flex flex-wrap gap-6 text-sm">
                                <span class="flex items-center gap-2">
                                    <CalendarDays
                                        class="text-muted-foreground size-4"
                                    />
                                    {{ t('event.date') }}
                                </span>
                                <span class="flex items-center gap-2">
                                    <GraduationCap
                                        class="text-muted-foreground size-4"
                                    />
                                    {{ t('event.location') }}
                                </span>
                            </div>

                            <div class="mt-8 flex flex-wrap items-center gap-4">
                                <Link
                                    v-if="!$page.props.auth.user"
                                    :href="register()"
                                >
                                    <Button size="lg">
                                        <UserPlus class="size-4" />
                                        {{ t('event.cta') }}
                                    </Button>
                                </Link>
                                <a
                                    :href="DISCORD_URL"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    v-else
                                >
                                    <Button size="lg">
                                        <MessageCircle class="size-4" />
                                        {{ t('event.cta') }}
                                    </Button>
                                </a>
                                <span class="text-muted-foreground text-sm">{{
                                    t('event.footer')
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="join" class="bg-muted/30 border-t">
                <div class="mx-auto w-full max-w-6xl px-4 py-20 sm:px-6">
                    <div class="max-w-2xl">
                        <h2
                            class="text-3xl font-bold tracking-tight sm:text-4xl"
                        >
                            {{ t('join.title') }}
                        </h2>
                        <p class="text-muted-foreground mt-4 text-lg">
                            {{ t('join.subtitle') }}
                        </p>
                    </div>

                    <div class="mt-12 grid gap-6 md:grid-cols-3">
                        <div
                            v-for="(step, index) in steps"
                            :key="step.title"
                            class="bg-background relative rounded-xl border p-6"
                        >
                            <span
                                class="text-muted-foreground/40 absolute top-4 right-5 text-4xl font-black"
                            >
                                {{ index + 1 }}
                            </span>
                            <span
                                class="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-cyan-600 text-white"
                            >
                                <component :is="step.icon" class="size-5" />
                            </span>
                            <h3 class="mt-4 font-semibold">
                                {{ t(step.title) }}
                            </h3>
                            <p
                                class="text-muted-foreground mt-2 text-sm leading-relaxed"
                            >
                                {{ t(step.desc) }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-10 flex flex-wrap justify-center gap-3">
                        <Link v-if="!$page.props.auth.user" :href="register()">
                            <Button size="lg" class="px-8">
                                <UserPlus class="size-4" />
                                {{ t('join.cta') }}
                                <ArrowRight class="size-4" />
                            </Button>
                        </Link>
                        <Link v-else :href="dashboard()">
                            <Button size="lg" class="px-8">
                                <Gamepad2 class="size-4" />
                                {{ t('nav.dashboard') }}
                                <ArrowRight class="size-4" />
                            </Button>
                        </Link>
                        <a
                            :href="DISCORD_URL"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Button variant="outline" size="lg" class="px-8">
                                <MessageCircle class="size-4" />
                                {{ t('join.ctaAlt') }}
                            </Button>
                        </a>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t">
            <div
                class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-10 sm:flex-row sm:items-center sm:justify-between sm:px-6"
            >
                <div class="flex items-center gap-2.5">
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-cyan-600 text-white"
                    >
                        <Boxes class="size-4" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold">Hytale Assos</p>
                        <p class="text-muted-foreground text-xs">
                            {{ t('footer.tagline') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <a
                        :href="DISCORD_URL"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-muted-foreground hover:text-foreground inline-flex items-center gap-2 text-sm transition-colors"
                    >
                        <MessageCircle class="size-4" />
                        {{ t('footer.discord') }}
                    </a>
                    <span
                        class="border-border hidden h-4 border-l sm:block"
                    ></span>
                    <p class="text-muted-foreground text-xs">
                        © {{ new Date().getFullYear() }} ·
                        {{ t('footer.rights') }}
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
