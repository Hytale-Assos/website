import { locale } from '@/composables/useLocale';

export function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat(locale.value, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
}

export function formatRelative(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    const diffSeconds = Math.round((date.getTime() - Date.now()) / 1000);

    const units: [Intl.RelativeTimeFormatUnit, number][] = [
        ['year', 60 * 60 * 24 * 365],
        ['month', 60 * 60 * 24 * 30],
        ['day', 60 * 60 * 24],
        ['hour', 60 * 60],
        ['minute', 60],
        ['second', 1],
    ];

    const formatter = new Intl.RelativeTimeFormat(locale.value, {
        numeric: 'auto',
    });

    for (const [unit, seconds] of units) {
        if (Math.abs(diffSeconds) >= seconds || unit === 'second') {
            return formatter.format(Math.round(diffSeconds / seconds), unit);
        }
    }

    return '—';
}

export function formatDuration(
    start: string | null,
    end: string | null,
): string | null {
    if (!start) {
        return null;
    }

    const startDate = new Date(start);
    const endDate = end ? new Date(end) : new Date();

    if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
        return null;
    }

    const totalMinutes = Math.max(
        0,
        Math.round((endDate.getTime() - startDate.getTime()) / 60000),
    );

    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    if (hours > 0) {
        return `${hours}h ${minutes.toString().padStart(2, '0')}m`;
    }

    return `${minutes}m`;
}
