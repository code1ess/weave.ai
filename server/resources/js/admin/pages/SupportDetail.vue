<script setup lang="ts">
import SupportThread from '../../shared/SupportThread.vue';
import type { SupportThreadProps } from '../../shared/support';
defineOptions({ inheritAttrs: false });
const props = defineProps<SupportThreadProps>();
</script>
<template><SupportThread v-bind="props" staff-view /></template>
