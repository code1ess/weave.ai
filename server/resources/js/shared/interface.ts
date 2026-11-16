import { computed, watchEffect } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import english from '../../../lang/en.json';

interface UiSettings {
  scope?: 'admin' | 'user';
  locale: string;
  locales: Record<string, string>;
  theme: 'light' | 'dark';
  direction: 'ltr' | 'rtl';
  messages: Record<string, string>;
}

export function useInterface() {
  const page = usePage<{ ui?: UiSettings }>();
  const locale = computed(() => page.props.ui?.locale ?? 'en');
  const theme = computed(() => page.props.ui?.theme ?? 'dark');
  const locales = computed(() => page.props.ui?.locales ?? { en: 'English' });

  function t(key: string, parameters: Record<string, unknown> = {}): string {
    const messages =
      page.props.ui?.messages ?? (english as Record<string, string>);
    let text = messages[key] ?? (english as Record<string, string>)[key] ?? key;
    for (const [name, value] of Object.entries(parameters)) {
      text = text.replaceAll(`{${name}}`, String(value));
    }
    return text;
  }

  function formatDate(value: string, dateOnly = false): string {
    const date = new Date(value);
    return dateOnly
      ? date.toLocaleDateString(locale.value)
      : date.toLocaleString(locale.value);
  }

  function formatNumber(value: number | string): string {
    return typeof value === 'string'
      ? BigInt(value).toLocaleString(locale.value)
      : value.toLocaleString(locale.value);
  }

  function setLocale(value: string) {
    router.post(
      '/preferences',
      { locale: value, scope: page.props.ui?.scope ?? 'user' },
      { preserveScroll: true, preserveState: false },
    );
  }

  function toggleTheme() {
    router.post(
      '/preferences',
      { theme: theme.value === 'dark' ? 'light' : 'dark' },
      { preserveScroll: true, preserveState: true },
    );
  }

  watchEffect(() => {
    if (typeof document === 'undefined') {
      return;
    }
    document.documentElement.dataset.theme = theme.value;
    document.documentElement.lang = locale.value;
    document.documentElement.dir = locale.value === 'ar' ? 'rtl' : 'ltr';
  });

  return {
    t,
    formatDate,
    formatNumber,
    locale,
    locales,
    theme,
    setLocale,
    toggleTheme,
  };
}
