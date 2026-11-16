<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { NAlert, NAvatar, NCard, NTag } from 'naive-ui';
import { useInterface } from '../../shared/interface';
import AdminIcon from '../AdminIcon.vue';
import { adminNavigation } from '../navigation';
import type { AdminIconName } from '../icons';

const { t, formatNumber } = useInterface();
const props = defineProps<{
  counts: { users: number; tasks: number; pending: number };
}>();
const page = usePage<{
  admin: { name: string; permissions: string[] } | null;
}>();
const shortcuts = computed(() =>
  adminNavigation.filter(
    (item) =>
      item.href !== '/admin' &&
      page.props.admin?.permissions.includes(item.permission),
  ),
);
const metrics = computed<
  {
    label: string;
    value: number;
    icon: AdminIconName;
    color: string;
    description: string;
  }[]
>(() => [
  {
    label: '注册用户',
    value: props.counts.users,
    icon: 'users',
    color: 'violet',
    description: '平台注册用户总数',
  },
  {
    label: '创作任务',
    value: props.counts.tasks,
    icon: 'tasks',
    color: 'blue',
    description: '已记录的创作请求总数',
  },
  {
    label: '等待处理',
    value: props.counts.pending,
    icon: 'clock',
    color: 'amber',
    description: '当前处于等待状态的任务',
  },
]);
const pendingPercent = computed(() =>
  props.counts.tasks > 0
    ? Math.min(
        100,
        Math.max(
          0,
          Math.round((props.counts.pending / props.counts.tasks) * 100),
        ),
      )
    : 0,
);
const taskLink = computed(() =>
  page.props.admin?.permissions.includes('tasks.view'),
);
</script>

<template>
  <Head :title="t('管理概览')" />
  <section class="dashboard">
    <div class="dashboard-heading">
      <div>
        <h1>{{ t('管理概览') }}</h1>
        <p>{{ t('平台运营，从这里开始。') }}</p>
      </div>
      <span class="dashboard-eyebrow">WEAVEAI / WORKSPACE</span>
    </div>
    <NCard class="dashboard-welcome" :bordered="false">
      <div class="welcome-content">
        <NAvatar
          :size="58"
          round
          color="#eceeff"
          :style="{ color: '#646cff', fontSize: '25px', fontWeight: '600' }"
          >{{ page.props.admin?.name.slice(0, 1).toUpperCase() }}</NAvatar
        >
        <div class="welcome-copy">
          <h2>
            {{ t('欢迎回来，{name}', { name: page.props.admin?.name ?? '' }) }}
          </h2>
          <p>{{ t('在这里管理创作任务、内容和日常运营。') }}</p>
        </div>
        <div class="welcome-brand" aria-hidden="true">
          <span>W</span>
          <div>WeaveAI<small>CREATIVE OPERATIONS</small></div>
        </div>
      </div>
    </NCard>
    <div class="dashboard-metrics">
      <NCard
        v-for="metric in metrics"
        :key="metric.label"
        :bordered="false"
        class="metric-card"
        :class="`metric-${metric.color}`"
      >
        <div class="metric-top">
          <span>{{ t(metric.label) }}</span
          ><span class="metric-icon"><AdminIcon :name="metric.icon" /></span>
        </div>
        <strong class="metric-value">{{ formatNumber(metric.value) }}</strong>
        <div class="metric-description">
          {{ t(metric.description)
          }}<span class="metric-badge">{{
            t(metric.color === 'amber' ? '当前' : '累计')
          }}</span>
        </div>
      </NCard>
    </div>
    <div class="dashboard-panels">
      <NCard
        :title="t('快捷入口')"
        :bordered="false"
        class="dashboard-shortcuts"
      >
        <template #header-extra
          ><span class="panel-subtitle">{{ t('常用管理功能') }}</span></template
        >
        <div v-if="shortcuts.length" class="shortcut-grid">
          <Link
            v-for="item in shortcuts"
            :key="item.href"
            :href="item.href"
            class="shortcut-link"
            ><span class="shortcut-icon"><AdminIcon :name="item.icon" /></span
            ><span>{{ t(item.label) }}</span
            ><AdminIcon name="arrow"
          /></Link>
        </div>
        <p v-else class="dashboard-empty">
          {{ t('当前角色暂无可用的管理入口。') }}
        </p>
      </NCard>
      <NCard :title="t('任务概况')" :bordered="false" class="dashboard-tasks">
        <template #header-extra
          ><NTag size="small" :bordered="false" round>{{
            t('当前状态')
          }}</NTag></template
        >
        <div class="task-overview">
          <div
            class="task-ring"
            :style="{ '--pending-angle': `${pendingPercent * 3.6}deg` }"
            role="img"
            :aria-label="`${t('等待处理')} ${pendingPercent}%`"
          >
            <div>
              <strong>{{ formatNumber(counts.tasks) }}</strong
              ><span>{{ t('创作任务') }}</span>
            </div>
          </div>
          <div class="task-legend">
            <p>
              <span class="legend-dot pending"></span>{{ t('等待处理')
              }}<strong>{{ formatNumber(counts.pending) }}</strong>
            </p>
            <p>
              <span class="legend-dot"></span>{{ t('其他状态')
              }}<strong>{{
                formatNumber(Math.max(0, counts.tasks - counts.pending))
              }}</strong>
            </p>
          </div>
        </div>
        <Link v-if="taskLink" href="/admin/tasks" class="dashboard-all-tasks"
          >{{ t('查看全部任务') }}<AdminIcon name="arrow"
        /></Link>
      </NCard>
    </div>
    <NAlert
      type="info"
      :bordered="false"
      class="dashboard-notice"
      :title="t('运营提示')"
      >{{
        t('当前生成任务仅记录，模型执行尚未接入。待处理数量不代表正在执行。')
      }}</NAlert
    >
  </section>
</template>

<style scoped>
.dashboard {
  display: flex;
  flex-direction: column;
  gap: 20px;
}
.dashboard-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.dashboard-heading h1 {
  margin: 0;
  font-size: 22px;
  font-weight: 600;
  letter-spacing: -0.5px;
}
.dashboard-heading p {
  margin: 5px 0 0;
  font-size: 12px;
  color: #8b90a1;
}
.dashboard-eyebrow {
  font-size: 10px;
  letter-spacing: 2px;
  color: #a1a6b5;
}
.dashboard-welcome {
  position: relative;
  overflow: hidden;
}
.dashboard-welcome::after {
  content: '';
  position: absolute;
  inset-block: -60px;
  inset-inline-end: 80px;
  width: 200px;
  background: linear-gradient(130deg, #f8f9ff, #eef0ff);
  transform: skewX(-25deg);
  pointer-events: none;
}
.welcome-content {
  display: flex;
  gap: 18px;
  align-items: center;
  min-height: 72px;
  position: relative;
}
.welcome-copy {
  flex: 1;
  z-index: 1;
}
.welcome-copy h2 {
  margin: 0 0 7px;
  font-size: 18px;
  font-weight: 600;
}
.welcome-copy p {
  margin: 0;
  color: #8b90a1;
  font-size: 12px;
}
.welcome-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  z-index: 1;
  color: #646cff;
  margin-inline-end: 22px;
}
.welcome-brand > span {
  font-size: 54px;
  font-style: italic;
  line-height: 1;
  font-weight: 800;
  letter-spacing: -9px;
  margin-inline-end: 6px;
}
.welcome-brand > div {
  font-weight: 650;
  font-size: 19px;
}
.welcome-brand small {
  display: block;
  font-size: 7px;
  font-weight: 500;
  letter-spacing: 1.5px;
  color: #999ee1;
}
.dashboard-metrics {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}
.metric-card {
  --metric-color: #7365e8;
  --metric-tint: #f0edff;
}
.metric-blue {
  --metric-color: #4397ec;
  --metric-tint: #eaf4ff;
}
.metric-amber {
  --metric-color: #e7a13d;
  --metric-tint: #fff6e7;
}
.metric-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: #7a8091;
  font-size: 13px;
}
.metric-icon {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: var(--metric-tint);
  color: var(--metric-color);
}
.metric-value {
  display: block;
  margin-block: 4px 12px;
  font-size: 34px;
  font-weight: 600;
  line-height: 1.4;
  letter-spacing: -1px;
  font-variant-numeric: tabular-nums;
}
.metric-description {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 8px;
  color: #989dad;
  font-size: 11px;
}
.metric-badge {
  padding: 2px 8px;
  border-radius: 4px;
  color: var(--metric-color);
  background: var(--metric-tint);
  font-size: 10px;
}
.dashboard-panels {
  display: grid;
  grid-template-columns: minmax(0, 1.6fr) minmax(290px, 1fr);
  gap: 20px;
  align-items: stretch;
}
.panel-subtitle {
  color: #a1a5b2;
  font-size: 11px;
}
.shortcut-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}
.shortcut-link {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
  padding: 15px 10px;
  border: 1px solid #f0f1f6;
  border-radius: 8px;
  color: #64697c;
  transition:
    background 0.15s,
    border-color 0.15s;
  font-size: 12px;
}
.shortcut-link:hover {
  background: #f7f8ff;
  border-color: #dedfff;
  color: #646cff;
}
.shortcut-icon {
  display: grid;
  flex-shrink: 0;
  place-items: center;
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: #f2f3ff;
  color: #797ff3;
}
.shortcut-icon svg {
  width: 17px;
}
.shortcut-link > svg {
  margin-inline-start: auto;
  width: 13px;
  opacity: 0.45;
}
.task-overview {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 26px;
  padding: 10px 0 20px;
}
.task-ring {
  display: grid;
  place-items: center;
  width: 132px;
  height: 132px;
  flex-shrink: 0;
  border-radius: 50%;
  background: conic-gradient(#8a8fff var(--pending-angle), #eef0f8 0);
}
.task-ring > div {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  width: 106px;
  height: 106px;
  border-radius: 50%;
  background: #fff;
}
.task-ring strong {
  font-size: 25px;
  font-weight: 600;
  line-height: 1.5;
}
.task-ring span {
  font-size: 10px;
  color: #989dad;
}
.task-legend {
  min-width: 106px;
}
.task-legend p {
  display: flex;
  align-items: center;
  gap: 7px;
  color: #858b9c;
  font-size: 11px;
}
.task-legend strong {
  margin-inline-start: auto;
  padding-inline-start: 8px;
  font-weight: 500;
  color: #555b70;
}
.legend-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #e2e5f2;
}
.legend-dot.pending {
  background: #8a8fff;
}
.dashboard-all-tasks {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-top: 1px solid #f0f1f6;
  padding-top: 14px;
  font-size: 12px;
}
.dashboard-all-tasks svg {
  width: 15px;
}
.dashboard-empty {
  padding: 30px 0;
  color: #989dad;
  text-align: center;
}
.dashboard-notice {
  font-size: 12px;
}
@media (max-width: 1250px) {
  .shortcut-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .task-overview {
    gap: 16px;
    flex-wrap: wrap;
  }
}
@media (max-width: 1050px) {
  .dashboard-panels {
    grid-template-columns: 1fr;
  }
  .shortcut-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .welcome-brand {
    display: none;
  }
}
@media (max-width: 600px) {
  .dashboard {
    gap: 14px;
  }
  .dashboard-eyebrow {
    display: none;
  }
  .dashboard-metrics {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  .metric-value {
    margin-block: 0 5px;
  }
  .shortcut-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .welcome-copy h2 {
    font-size: 16px;
  }
  .welcome-content {
    gap: 12px;
  }
  .welcome-copy p {
    max-width: 200px;
  }
}
</style>
