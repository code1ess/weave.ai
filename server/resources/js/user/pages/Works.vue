<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
  taskStatusLabels,
  taskKindLabels,
  type TaskRecord,
} from '../../shared/generation';

const { t, formatDate } = useInterface();

const props = defineProps<{
  tasks: {
    data: TaskRecord[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  filters: { kind?: string; status?: string };
}>();
const filters = reactive({
  kind: props.filters.kind ?? '',
  status: props.filters.status ?? '',
});

function filter() {
  router.get('/works', filters);
}
</script>
<template>
  <Head :title="t('我的创作')" />
  <p class="text-sm text-orange-700">YOUR CREATIVE JOURNEY</p>
  <h1 class="mt-3 text-4xl">{{ t('我的创作') }}</h1>
  <p class="mt-5 text-sm text-stone-500">
    {{ t('查看每一次提交和处理记录。生成服务尚未开放，当前没有生成产物。') }}
  </p>
  <form class="my-8 flex flex-wrap gap-4" @submit.prevent="filter">
    <select
      v-model="filters.kind"
      :aria-label="t('创作类型')"
      class="rounded-lg border bg-white p-3"
    >
      <option value="">{{ t('全部类型') }}</option>
      <option v-for="(label, key) in taskKindLabels" :key="key" :value="key">
        {{ t(label) }}
      </option></select
    ><select
      v-model="filters.status"
      :aria-label="t('任务状态')"
      class="rounded-lg border bg-white p-3"
    >
      <option value="">{{ t('全部状态') }}</option>
      <option v-for="(label, key) in taskStatusLabels" :key="key" :value="key">
        {{ t(label) }}
      </option></select
    ><button class="rounded-full bg-stone-900 px-6 py-3 text-white">
      {{ t('筛选') }}
    </button>
  </form>
  <p
    v-if="!tasks.data.length"
    class="rounded-2xl border border-dashed p-10 text-stone-500"
  >
    {{ t('暂无符合条件的任务。')
    }}<Link href="/studio" class="underline">{{ t('开始创作') }}</Link>
  </p>
  <section class="grid gap-5 md:grid-cols-3">
    <Link
      v-for="task in tasks.data"
      :key="task.id"
      :href="`/works/${task.id}`"
      class="rounded-2xl border border-stone-200 bg-white p-6"
      ><img
        v-if="task.asset_id"
        :src="`/assets/${task.asset_id}`"
        :alt="t('任务参考图')"
        loading="lazy"
        class="mb-5 aspect-square w-full rounded-xl object-cover"
      />
      <p class="text-xs text-orange-700">
        {{ t(taskKindLabels[task.kind]) }} ·
        {{ t(taskStatusLabels[task.status] ?? task.status) }}
      </p>
      <p class="mt-4 line-clamp-3 whitespace-pre-wrap break-words">
        {{ task.prompt }}
      </p>
      <time class="mt-5 block text-xs text-stone-400">{{
        formatDate(task.created_at)
      }}</time></Link
    >
  </section>
  <nav class="mt-8 flex gap-6">
    <Link v-if="tasks.prev_page_url" :href="tasks.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="tasks.next_page_url" :href="tasks.next_page_url">{{
      t('下一页')
    }}</Link>
  </nav>
</template>
