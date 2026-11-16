<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const { t, formatDate } = useInterface();

defineProps<{
  assets: {
    data: {
      id: string;
      status: string;
      size: number;
      task_count: number;
      created_at: string;
    }[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
}>();
const deletion = useForm({ asset: '' });
const selected = ref<string | null>(null);

function remove() {
  if (!selected.value) {
    return;
  }

  deletion.delete(`/assets/${selected.value}`, {
    preserveScroll: true,
    onSuccess: () => {
      selected.value = null;
    },
  });
}
</script>
<template>
  <Head :title="t('我的素材')" />
  <p class="text-sm text-orange-700">YOUR SOURCE MATERIAL</p>
  <h1 class="mt-3 text-4xl">{{ t('我的素材') }}</h1>
  <p class="mt-5 mb-8 text-sm text-stone-500">
    {{
      t('复用已上传的参考图片。任务引用的图片会保留，未使用的图片可以删除。')
    }}
  </p>
  <p v-if="deletion.errors.asset" role="alert" class="mb-5 text-red-700">
    {{ deletion.errors.asset }}
  </p>
  <div
    v-if="selected"
    class="mb-8 rounded-2xl border border-orange-200 bg-orange-50 p-6"
    role="alert"
  >
    <p>{{ t('确认删除这张未使用的图片？文件将从存储中移除。') }}</p>
    <div class="mt-4 flex gap-6">
      <button :disabled="deletion.processing" @click="remove">
        {{ deletion.processing ? t('删除中…') : t('确认删除') }}</button
      ><button :disabled="deletion.processing" @click="selected = null">
        {{ t('取消') }}
      </button>
    </div>
  </div>
  <p
    v-if="!assets.data.length"
    class="rounded-2xl border border-dashed p-10 text-stone-500"
  >
    {{ t('还没有上传的素材。')
    }}<Link href="/studio" class="underline">{{ t('前往工作台上传') }}</Link>
  </p>
  <section class="grid gap-6 md:grid-cols-3">
    <article
      v-for="asset in assets.data"
      :key="asset.id"
      class="rounded-2xl bg-white p-5"
    >
      <img
        v-if="asset.status === 'active'"
        :src="`/assets/${asset.id}`"
        :alt="t('已上传素材')"
        loading="lazy"
        class="aspect-square w-full rounded-xl object-cover"
      />
      <p v-else class="rounded-xl bg-stone-100 p-10 text-sm text-stone-500">
        {{ t('删除待重试，图片已停止使用。') }}
      </p>
      <p class="mt-4 text-xs text-stone-500">
        {{ (asset.size / 1024 / 1024).toFixed(2) }} MB · {{ asset.task_count }}
        {{ t('个任务引用') }}
      </p>
      <time class="mt-2 block text-xs text-stone-400">{{
        formatDate(asset.created_at)
      }}</time>
      <div class="mt-5 flex gap-5 text-sm">
        <Link
          v-if="asset.status === 'active'"
          :href="`/studio?asset=${asset.id}`"
          >{{ t('用于创作 ↗') }}</Link
        ><button
          v-if="asset.task_count === 0"
          :disabled="deletion.processing"
          @click="selected = asset.id"
        >
          {{ asset.status === 'deleting' ? t('重试删除') : t('删除') }}
        </button>
      </div>
    </article>
  </section>
  <nav class="mt-8 flex gap-6">
    <Link v-if="assets.prev_page_url" :href="assets.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="assets.next_page_url" :href="assets.next_page_url">{{
      t('下一页')
    }}</Link>
  </nav>
</template>
