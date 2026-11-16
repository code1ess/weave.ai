<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { NInput, NSelect, NButton, NCard, NTag } from 'naive-ui';

const { t } = useInterface();

const props = defineProps<{
  search: string;
  moderation: string;
  records: {
    data: {
      id: string;
      user_id: number;
      kind: string;
      status: string;
      moderation_status: string;
      created_at: string;
    }[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
}>();
const search = ref(props.search);
const moderation = ref(props.moderation);

function filter() {
  router.get('/admin/tasks', {
    search: search.value,
    moderation: moderation.value,
  });
}
</script>
<template>
  <Head :title="t('生成任务')" />
  <h1>{{ t('生成任务') }}</h1>
  <form
    style="display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px"
    @submit.prevent="filter"
  >
    <NInput
      v-model:value="search"
      :placeholder="t('完整任务 ID')"
      style="max-width: 400px"
      :maxlength="100"
    /><NSelect
      v-model:value="moderation"
      style="max-width: 200px"
      :options="[
        { label: t('全部审核状态'), value: '' },
        { label: t('待审核'), value: 'pending' },
        { label: t('已通过'), value: 'approved' },
        { label: t('已拒绝'), value: 'rejected' },
      ]"
    /><NButton attr-type="submit">{{ t('查询') }}</NButton>
  </form>
  <p v-if="!records.data.length">{{ t('暂无任务。') }}</p>
  <NCard v-for="task in records.data" :key="task.id" style="margin-bottom: 16px"
    ><Link :href="`/admin/tasks/${task.id}`" style="overflow-wrap: anywhere">{{
      task.id
    }}</Link>
    <p>
      {{ t('用户') }} {{ task.user_id }} · {{ task.kind }} ·
      {{ task.created_at }}
    </p>
    <NTag>{{ task.status }} · {{ task.moderation_status }}</NTag></NCard
  >
  <nav style="display: flex; gap: 24px">
    <Link v-if="records.prev_page_url" :href="records.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="records.next_page_url" :href="records.next_page_url">{{
      t('下一页')
    }}</Link>
  </nav>
</template>
