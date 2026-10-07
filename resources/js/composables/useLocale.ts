import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Locale, Messages } from '@/lang';
import { messages } from '@/lang';

const STORAGE_KEY = 'locale';

const isLocale = (value: string | null): value is Locale =>
    value === 'fr' || value === 'en';

const detected = (): Locale => {
    if (typeof navigator === 'undefined') {
        return 'fr';
    }

    const languages = navigator.languages?.length
        ? navigator.languages
        : [navigator.language];

    return languages.some((language) => language.toLowerCase().startsWith('fr'))
        ? 'fr'
        : 'en';
};

const stored = (): Locale => {
    if (typeof window === 'undefined') {
        return 'fr';
    }

    const value = localStorage.getItem(STORAGE_KEY);

    return isLocale(value) ? value : detected();
};

export const locale = ref<Locale>(stored());

export function setLocale(value: Locale): void {
    locale.value = value;

    if (typeof window !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, value);
        document.documentElement.lang = value;
    }
}

export type TranslateParams = Record<string, string | number>;

export type UseLocaleReturn = {
    locale: Ref<Locale>;
    messages: ComputedRef<Messages>;
    t: (key: string, params?: TranslateParams) => string;
    setLocale: (value: Locale) => void;
};

export function interpolate(
    template: string,
    params?: TranslateParams,
): string {
    if (!params) {
        return template;
    }

    return template.replace(/\{(\w+)\}/g, (match, name: string) =>
        name in params ? String(params[name]) : match,
    );
}

export function useLocale(): UseLocaleReturn {
    const current = computed(() => messages[locale.value]);

    return {
        locale,
        messages: current,
        t: (key: string, params?: TranslateParams) =>
            interpolate(current.value[key] ?? key, params),
        setLocale,
    };
}
