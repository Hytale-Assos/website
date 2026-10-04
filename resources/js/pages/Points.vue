<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Award, Clock, Flame, RefreshCw, Target } from '@lucide/vue';
import MemberPage from '@/components/dashboard/MemberPage.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useLocale } from '@/composables/useLocale';
import { formatDateTime } from '@/lib/hytale';
import { index as pointsIndex } from '@/routes/points';

type PointsWeek = {
    start: string;
    end: string;
    hours: number;
    rewarded: boolean;
    carryIn: number;
};

const props = defineProps<{
    points: {
        points: number;
        hoursTotal: number;
        hoursSinceLastReward: number;
        hoursToNextPoint: number;
        nextRewardAt: string | null;
        weeks: PointsWeek[];
        maxSessionPoints: number;
        sessionPointsCapped: boolean;
    };
    hytaleId: string | null;
    error: string | null;
    mock: boolean;
}>();

const { t } = useLocale();

const threshold = 2;

const progress = computed(() => {
    if (props.points.sessionPointsCapped) {
        return 100;
    }

    const hours = Math.min(props.points.hoursSinceLastReward, threshold);

    return Math.round((hours / threshold) * 100);
});

const refresh = () => router.reload();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Points Open', href: pointsIndex() }],
    },
});
</script>

<template>
    <Head :title="t('dash.points.title')" />

    <MemberPage
        :title="t('dash.points.title')"
        :description="t('dash.points.description')"
        :error="error"
        :mock="mock"
        :hytale-id="hytaleId"
        require-hytale
    >
        <template #actions>
            <Button variant="outline" size="sm" @click="refresh">
                <RefreshCw class="size-4" />
                {{ t('dash.refresh') }}
            </Button>
        </template>

        <div class="grid gap-4 sm:grid-cols-3">
            <Card>
                <CardContent class="flex items-center gap-4">
                    <span
                        class="flex size-11 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500"
                    >
                        <Award class="size-5" />
                    </span>
                    <div>
                        <p class="text-muted-foreground text-xs font-medium">
                            {{ t('dash.points.balance') }}
                        </p>
                        <p class="text-3xl font-bold tracking-tight">
                            {{ props.points.points.toFixed(1) }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="flex items-center gap-4">
                    <span
                        class="bg-muted text-muted-foreground flex size-11 items-center justify-center rounded-xl"
                    >
                        <Clock class="size-5" />
                    </span>
                    <div>
                        <p class="text-muted-foreground text-xs font-medium">
                            {{ t('dash.points.totalHours') }}
                        </p>
                        <p class="text-3xl font-bold tracking-tight">
                            {{ props.points.hoursTotal.toFixed(1) }} h
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="flex items-center gap-4">
                    <span
                        class="flex size-11 items-center justify-center rounded-xl"
                        :class="
                            props.points.hoursToNextPoint <= 0
                                ? 'bg-emerald-500/10 text-emerald-500'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        <Target class="size-5" />
                    </span>
                    <div>
                        <p class="text-muted-foreground text-xs font-medium">
                            {{ t('dash.points.progress') }}
                        </p>
                        <p class="text-2xl font-bold tracking-tight">
                            {{ progress }}%
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardContent class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-medium">
                        <template v-if="props.points.sessionPointsCapped">
                            {{
                                t('dash.points.capReached', {
                                    points: props.points.points.toFixed(1),
                                })
                            }}
                        </template>
                        <template v-else-if="props.points.hoursToNextPoint > 0">
                            {{
                                t('dash.points.toNext', {
                                    hours: props.points.hoursToNextPoint.toFixed(
                                        1,
                                    ),
                                })
                            }}
                        </template>
                        <template v-else>
                            {{ t('dash.points.ready') }}
                        </template>
                    </p>
                    <Flame
                        v-if="
                            !props.points.sessionPointsCapped &&
                            props.points.hoursToNextPoint <= 0
                        "
                        class="size-4 text-emerald-500"
                    />
                </div>

                <div class="bg-muted h-2.5 w-full overflow-hidden rounded-full">
                    <div
                        class="h-full rounded-full transition-all"
                        :class="
                            props.points.sessionPointsCapped
                                ? 'bg-amber-500'
                                : 'bg-gradient-to-r from-emerald-500 to-cyan-600'
                        "
                        :style="{ width: progress + '%' }"
                    ></div>
                </div>

                <div
                    class="flex flex-wrap items-center justify-between gap-2 text-xs"
                >
                    <span class="text-muted-foreground">
                        {{
                            t('dash.points.capProgress', {
                                points: props.points.points.toFixed(1),
                                max: props.points.maxSessionPoints.toFixed(1),
                            })
                        }}
                    </span>
                    <span
                        v-if="props.points.nextRewardAt"
                        class="text-muted-foreground"
                    >
                        {{
                            t('dash.points.unlockAt', {
                                date: formatDateTime(props.points.nextRewardAt),
                            })
                        }}
                    </span>
                </div>

                <p class="text-muted-foreground text-xs">
                    {{ t('dash.points.rule') }}
                </p>
            </CardContent>
        </Card>

        <div class="space-y-3">
            <h2 class="text-sm font-semibold">
                {{ t('dash.points.history') }}
            </h2>

            <div
                v-if="props.points.weeks.length === 0"
                class="border-border/70 text-muted-foreground rounded-xl border border-dashed px-4 py-8 text-center text-sm"
            >
                {{ t('dash.points.empty') }}
            </div>

            <div v-else class="space-y-2">
                <Card v-for="week in props.points.weeks" :key="week.start">
                    <CardContent class="flex flex-wrap items-center gap-4">
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                            :class="
                                week.rewarded
                                    ? 'bg-emerald-500/10 text-emerald-500'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            <Award class="size-4" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{
                                    t('dash.points.week', {
                                        date: formatDateTime(week.start),
                                    })
                                }}
                            </p>
                            <p class="text-muted-foreground text-xs">
                                {{
                                    t('dash.points.weekHours', {
                                        hours: week.hours.toFixed(1),
                                    })
                                }}
                                <template v-if="week.carryIn > 0">
                                    ·
                                    {{
                                        t('dash.points.carry', {
                                            hours: week.carryIn.toFixed(1),
                                        })
                                    }}
                                </template>
                            </p>
                        </div>

                        <Badge
                            :variant="week.rewarded ? 'default' : 'secondary'"
                        >
                            {{
                                week.rewarded
                                    ? t('dash.points.rewarded')
                                    : t('dash.points.notRewarded')
                            }}
                        </Badge>
                    </CardContent>
                </Card>
            </div>
        </div>
    </MemberPage>
</template>
