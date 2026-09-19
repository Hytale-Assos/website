import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import type { Locale, Messages } from '@/lang';
import { messages } from '@/lang';

const STORAGE_KEY = 'locale';

const isLocale = (value: string | null): value is Locale =>
    value === 'fr' || value === 'en';

const stored = (): Locale => {
    if (typeof window === 'undefined') {
        return 'fr';
    }

    const value = localStorage.getItem(STORAGE_KEY);

    return isLocale(value) ? value : 'fr';
};

export const locale = ref<Locale>(stored());

export function setLocale(value: Locale): void {
    locale.value = value;

    if (typeof window !== 'undefined') {
        localStorage.setItem(STORAGE_KEY, value);
        document.documentElement.lang = value;
    }
}

export type UseLocaleReturn = {
    locale: Ref<Locale>;
    messages: ComputedRef<Messages>;
    t: (key: string) => string;
    setLocale: (value: Locale) => void;
};

export function useLocale(): UseLocaleReturn {
    const current = computed(() => messages[locale.value]);

    return {
        locale,
        messages: current,
        t: (key: string) => current.value[key] ?? key,
        setLocale,
    };
}
