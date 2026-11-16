<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { NCard, NSelect, NButton, NSpace, NTag } from 'naive-ui';

const { t, formatDate } = useInterface();

const props = defineProps<{
  status: string;
  tickets: {
    data: {
      id: string;
      user_id: number;
      subject: string;
      status: string;
      updated_at: string;
    }[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
}>();
const statusFilter = ref(props.status);
const labels: Record<string, string> = {
  open: t('待处理'),
  in_progress: t('处理中'),
  resolved: t('已解决'),
};
const options = [
  { label: t('全部工单'), value: '' },
  ...Object.entries(labels).map(([value, label]) => ({ value, label })),
];

function filter() {
  router.get('/admin/support', { status: statusFilter.value });
}
</script>
<template>
  <Head :title="t('客服工单')" />
  <h1>{{ t('客服工单') }}</h1>
  <form
    style="display: flex; gap: 16px; margin-bottom: 24px"
    @submit.prevent="filter"
  >
    <NSelect
      v-model:value="statusFilter"
      :options="options"
      style="max-width: 300px"
    /><NButton attr-type="submit">{{ t('筛选') }}</NButton>
  </form>
  <p v-if="!tickets.data.length">{{ t('暂无工单。') }}</p>
  <NCard
    v-for="ticket in tickets.data"
    :key="ticket.id"
    style="margin-bottom: 16px"
    ><NSpace
      ><NTag>{{ labels[ticket.status] }}</NTag
      ><span>{{ t('用户') }} {{ ticket.user_id }}</span></NSpace
    >
    <h2>
      <Link :href="`/admin/support/${ticket.id}`">{{ ticket.subject }}</Link>
    </h2>
    <time>{{ formatDate(ticket.updated_at) }}</time></NCard
  ><NSpace
    ><Link v-if="tickets.prev_page_url" :href="tickets.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="tickets.next_page_url" :href="tickets.next_page_url">{{
      t('下一页')
    }}</Link></NSpace
  >
</template>
