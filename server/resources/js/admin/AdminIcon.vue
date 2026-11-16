<script setup lang="ts">
import { iconPaths, type AdminIconName } from './icons';

defineProps<{ name: AdminIconName }>();
</script>

<template>
  <svg
    width="20"
    height="20"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.7"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
    focusable="false"
  >
    <path :d="iconPaths[name]" />
  </svg>
</template>
