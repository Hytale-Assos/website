import type { Ref } from 'vue';
import { onMounted, ref } from 'vue';

export type FontSize = 'system' | 's' | 'm' | 'l' | 'xl' | 'xxl';

export type UseAccessibilityReturn = {
    fontSize: Ref<FontSize>;
    updateFontSize: (value: FontSize) => void;
    dyslexicFont: Ref<boolean>;
    updateDyslexicFont: (enabled: boolean) => void;
};

const FONT_SIZE_SCALES: Record<Exclude<FontSize, 'system'>, number> = {
    s: 0.9,
    m: 1,
    l: 1.1,
    xl: 1.25,
    xxl: 1.5,
};

const isFontSize = (value: string | null): value is FontSize =>
    value === 'system' ||
    value === 's' ||
    value === 'm' ||
    value === 'l' ||
    value === 'xl' ||
    value === 'xxl';

export function applyFontSize(value: FontSize): void {
    if (typeof document === 'undefined') {
        return;
    }

    if (value === 'system') {
        // Let the browser font size drive the rem-based layout.
        document.documentElement.style.removeProperty('font-size');
    } else {
        document.documentElement.style.fontSize = `${FONT_SIZE_SCALES[value] * 100}%`;
    }
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const fontSize = ref<FontSize>('system');

export function applyDyslexicFont(enabled: boolean): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.classList.toggle('font-dyslexic', enabled);
}

const dyslexicFont = ref(false);

export function initializeDyslexicFont(): void {
    if (typeof window === 'undefined') {
        return;
    }

    dyslexicFont.value = localStorage.getItem('dyslexic_font') === '1';

    applyDyslexicFont(dyslexicFont.value);
}

export function initializeFontSize(): void {
    if (typeof window === 'undefined') {
        return;
    }

    const storedFontSize = localStorage.getItem('font_size');

    if (isFontSize(storedFontSize)) {
        fontSize.value = storedFontSize;
    }

    applyFontSize(fontSize.value);
}

export function useAccessibility(): UseAccessibilityReturn {
    onMounted(() => {
        const storedFontSize = localStorage.getItem('font_size');

        if (isFontSize(storedFontSize)) {
            fontSize.value = storedFontSize;
        }
    });

    function updateFontSize(value: FontSize): void {
        fontSize.value = value;

        // Store in localStorage for client-side persistence...
        localStorage.setItem('font_size', value);

        // Store in cookie for server-side awareness...
        setCookie('font_size', value);

        applyFontSize(value);
    }

    function updateDyslexicFont(enabled: boolean): void {
        dyslexicFont.value = enabled;

        localStorage.setItem('dyslexic_font', enabled ? '1' : '0');
        setCookie('dyslexic_font', enabled ? '1' : '0');

        applyDyslexicFont(enabled);
    }

    return {
        fontSize,
        updateFontSize,
        dyslexicFont,
        updateDyslexicFont,
    };
}
