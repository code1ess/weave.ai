<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { reactive } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { NCard, NStatistic, NAlert, NButton, NDataTable } from 'naive-ui';

const { t, formatNumber } = useInterface();

interface Group {
  label: string;
  total: number;
}
const props = defineProps<{
  filters: { from: string; to: string };
  timezone: string;
  summary: { registrations: number; tasks: number };
  byStatus: Group[];
  byKind: Group[];
  byModeration: Group[];
  daily: { day: string; tasks: number; registrations: number }[];
  financial: {
    increased: string;
    decreased: string;
    net: string;
    available: string;
    reserved: string;
  } | null;
}>();
const page = usePage<{ errors: Record<string, string> }>();
const filters = reactive({ ...props.filters });
const groupColumns = [
  { title: t('分类'), key: 'label' },
  { title: t('数量'), key: 'total' },
];
const dailyColumns = [
  { title: t('日期'), key: 'day' },
  { title: t('注册'), key: 'registrations' },
  { title: t('任务提交'), key: 'tasks' },
];
const labels: Record<string, string> = {
  pending: t('等待处理'),
  processing: t('处理中'),
  cancelled: t('已取消'),
  rejected: t('已拒绝'),
  approved: t('已通过'),
  succeeded: t('已完成'),
  failed: t('失败'),
  text_to_image: t('文字生图'),
  image_to_image: t('图片编辑'),
  image_to_video: t('照片动画'),
};

function rows(groups: Group[]) {
  return groups.map((group) => ({
    ...group,
    label: labels[group.label] ?? group.label,
  }));
}

function formatCredits(value: string) {
  return formatNumber(value);
}
</script>
<template>
  <Head :title="t('运营报表')" />
  <h1>{{ t('运营报表') }}</h1>
  <NAlert type="info" style="margin-bottom: 24px"
    >{{ t('按') }} {{ timezone }}
    {{
      t(
        '统计，最多查询 93 天。任务状态为所选日期创建任务的当前状态；生成尚未接入，任务数量不代表已出片数量。',
      )
    }}</NAlert
  >
  <form class="filters" @submit.prevent="router.get('/admin/reports', filters)">
    <label
      >{{ t('开始日期')
      }}<input v-model="filters.from" type="date" required /></label
    ><label
      >{{ t('结束日期')
      }}<input v-model="filters.to" type="date" required /></label
    ><NButton attr-type="submit">{{ t('查询') }}</NButton>
  </form>
  <NAlert
    v-for="error in page.props.errors"
    :key="error"
    type="error"
    style="margin-bottom: 16px"
    >{{ error }}</NAlert
  >
  <div class="cards">
    <NCard
      ><NStatistic
        :label="t('期间注册用户')"
        :value="summary.registrations" /></NCard
    ><NCard
      ><NStatistic :label="t('期间创建任务')" :value="summary.tasks"
    /></NCard>
  </div>
  <div class="cards">
    <NCard :title="t('任务状态')"
      ><NDataTable :columns="groupColumns" :data="rows(byStatus)" /></NCard
    ><NCard :title="t('任务类型')"
      ><NDataTable :columns="groupColumns" :data="rows(byKind)" /></NCard
    ><NCard :title="t('当前审核状态')"
      ><NDataTable :columns="groupColumns" :data="rows(byModeration)"
    /></NCard>
  </div>
  <NCard
    v-if="financial"
    :title="t('点数汇总（仅财务权限可见）')"
    style="margin: 24px 0"
    ><NAlert type="warning">{{
      t(
        '以下为点数变动，不是现金收入。期间变动按筛选日期，当前余额为全部钱包即时汇总。',
      )
    }}</NAlert>
    <div class="cards">
      <p>{{ t('期间增加：') }}{{ formatCredits(financial.increased) }}</p>
      <p>{{ t('期间扣回：') }}{{ formatCredits(financial.decreased) }}</p>
      <p>{{ t('期间净变动：') }}{{ formatCredits(financial.net) }}</p>
      <p>{{ t('当前可用：') }}{{ formatCredits(financial.available) }}</p>
      <p>{{ t('当前预占：') }}{{ formatCredits(financial.reserved) }}</p>
    </div></NCard
  ><NCard :title="t('每日注册与任务提交')"
    ><NDataTable
      :columns="dailyColumns"
      :data="daily"
      :pagination="{ pageSize: 15 }"
  /></NCard>
</template>
<style scoped>
.filters {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
  align-items: end;
  margin-bottom: 24px;
}
label {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
input {
  border: 1px solid #ccc;
  border-radius: 6px;
  padding: 8px;
}
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin: 24px 0;
}
</style>
