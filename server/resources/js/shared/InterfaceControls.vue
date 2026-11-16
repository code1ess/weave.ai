<script setup lang="ts">
import { useInterface } from './interface';
const { t, locale, locales, theme, setLocale, toggleTheme } = useInterface();
</script>
<template>
  <div class="interface-controls">
    <label class="interface-language"
      ><span class="sr-only">{{ t('语言') }}</span
      ><span aria-hidden="true">◎</span
      ><select
        :value="locale"
        :aria-label="t('语言')"
        @change="setLocale(($event.target as HTMLSelectElement).value)"
      >
        <option v-for="(label, code) in locales" :key="code" :value="code">
          {{ label }}
        </option>
      </select></label
    >
    <button
      class="interface-theme"
      type="button"
      :aria-label="theme === 'dark' ? t('明亮主题') : t('深色主题')"
      :title="theme === 'dark' ? t('明亮主题') : t('深色主题')"
      @click="toggleTheme"
    >
      <span aria-hidden="true">{{ theme === 'dark' ? '☀' : '☾' }}</span>
    </button>
  </div>
</template>
<style>
.interface-controls {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}
.interface-language {
  display: flex;
  align-items: center;
  gap: 5px;
  border: 1px solid var(--line, #555);
  border-radius: 10px;
  padding: 7px 10px;
}
.interface-language select {
  background: transparent;
  color: inherit;
  border: 0;
  max-width: 140px;
  outline-offset: 4px;
  font: inherit;
}
.interface-language option {
  background: var(--surface, #17191f);
  color: var(--text, #f5f5f5);
}
.interface-theme {
  width: 36px;
  height: 36px;
  border: 1px solid var(--line, #555);
  border-radius: 10px;
  background: transparent;
  color: inherit;
  cursor: pointer;
  font-size: 19px;
}
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
