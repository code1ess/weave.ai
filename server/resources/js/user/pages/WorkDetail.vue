<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { Head, Link, useForm } from '@inertiajs/vue3';
import {
  taskStatusLabels,
  taskKindLabels,
  type TaskRecord,
} from '../../shared/generation';

const { t, formatDate } = useInterface();

defineProps<{
  task: TaskRecord;
  template: { title: string; version: number } | null;
  review: { decision: string; reason: string } | null;
  events: { id: number; type: string; created_at: string }[];
}>();
const cancellation = useForm({ task: '' });
</script>
<template>
  <Head :title="t('创作记录')" /><Link
    href="/works"
    class="text-sm text-stone-500"
    >{{ t('← 返回我的创作') }}</Link
  >
  <article class="mt-8 rounded-3xl bg-white p-8">
    <p class="text-sm text-orange-700">
      {{ t(taskKindLabels[task.kind]) }} ·
      {{ t(taskStatusLabels[task.status] ?? task.status) }}
    </p>
    <h1 class="mt-4 text-3xl">{{ t('创作记录') }}</h1>
    <p class="mt-4 break-all font-mono text-xs text-stone-400">{{ task.id }}</p>
    <p class="mt-3 text-sm text-stone-500">
      {{ formatDate(task.created_at) }}
    </p>
    <p v-if="template" class="mt-5 text-sm">
      {{ t('模板：') }}{{ template.title }} {{ t('· 版本') }}
      {{ template.version }}
    </p>
    <img
      v-if="task.asset_id"
      :src="`/assets/${task.asset_id}`"
      :alt="t('任务参考图')"
      class="mt-6 max-h-96 max-w-full rounded-xl"
    />
    <p v-if="task.parameters" class="mt-5 text-sm text-stone-500">
      {{ t('画面比例') }} {{ task.parameters.aspect_ratio
      }}<template v-if="task.parameters.duration_seconds">
        {{ t('· 期望') }} {{ task.parameters.duration_seconds }}
        {{ t('秒') }}</template
      >
      {{ t('· 接单配置 v') }}{{ task.mode_version }}
    </p>
    <h2 class="mt-8 font-semibold">{{ t('创意描述') }}</h2>
    <p class="mt-4 whitespace-pre-wrap break-words leading-8">
      {{ task.prompt }}
    </p>
    <p v-if="review" class="mt-6 rounded-xl bg-stone-50 p-4 text-sm">
      {{ t('审核')
      }}{{ review.decision === 'approved' ? t('通过') : t('拒绝') }}：{{
        review.reason
      }}
    </p>
    <p v-if="cancellation.errors.task" role="alert" class="mt-4 text-red-700">
      {{ cancellation.errors.task }}
    </p>
    <div class="mt-8 flex flex-wrap gap-6 text-sm">
      <button
        v-if="task.status === 'pending'"
        :disabled="cancellation.processing"
        class="underline"
        @click="cancellation.post(`/tasks/${task.id}/cancel`)"
      >
        {{ t('取消任务') }}</button
      ><Link :href="`/support?task=${task.id}`" class="underline">{{
        t('反馈问题')
      }}</Link>
    </div>
  </article>
  <section class="mt-8">
    <h2 class="text-xl">{{ t('最近处理记录') }}</h2>
    <p v-if="!events.length" class="mt-4 text-sm text-stone-500">
      {{ t('任务已记录，等待后续处理。') }}
    </p>
    <p
      v-for="event in events"
      :key="event.id"
      class="mt-3 text-sm text-stone-600"
    >
      {{ formatDate(event.created_at) }} · {{ event.type }}
    </p>
  </section>
</template>
