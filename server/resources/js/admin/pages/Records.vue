<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { NDataTable, NInput, NButton } from 'naive-ui';

const { t } = useInterface();

const props = defineProps<{
  title: string;
  search: string | null;
  records: {
    data: Record<string, string | number | null>[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
}>();
const query = ref(props.search ?? '');
const columns = computed(() =>
  Object.keys(props.records.data[0] ?? {}).map((key) => ({ title: key, key })),
);

function applySearch() {
  router.get(
    location.pathname,
    { search: query.value },
    { preserveState: true },
  );
}
</script>
<template>
  <Head :title="title" />
  <h1>{{ title }}</h1>
  <form
    v-if="search !== null"
    style="display: flex; gap: 12px; margin-bottom: 20px"
    @submit.prevent="applySearch"
  >
    <NInput
      v-model:value="query"
      :placeholder="t('用户姓名/邮箱或完整任务 ID')"
      :input-props="{ maxlength: 100 }"
    /><NButton attr-type="submit">{{ t('搜索') }}</NButton>
  </form>
  <NDataTable :columns="columns" :data="records.data" :scroll-x="900" />
  <div style="display: flex; gap: 24px; margin-top: 20px">
    <Link v-if="records.prev_page_url" :href="records.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="records.next_page_url" :href="records.next_page_url">{{
      t('下一页')
    }}</Link>
  </div>
</template>
