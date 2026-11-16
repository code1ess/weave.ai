<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { Head, Link } from '@inertiajs/vue3';

const { t } = useInterface();

defineProps<{
  articles: {
    data: { id: string; slug: string; title: string; body: string }[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
}>();
</script>
<template>
  <Head :title="t('帮助中心')" />
  <p class="text-sm text-orange-700">A LITTLE GUIDANCE</p>
  <h1 class="mt-3 text-4xl">{{ t('帮助中心') }}</h1>
  <p class="mt-5 mb-8 text-stone-500">
    {{ t('了解如何开始创作，或')
    }}<Link href="/support" class="underline">{{ t('联系支持') }}</Link
    >。
  </p>
  <p
    v-if="!articles.data.length"
    class="rounded-2xl border border-dashed p-8 text-stone-500"
  >
    {{ t('帮助内容正在整理。遇到问题可提交客服工单。') }}
  </p>
  <div class="grid gap-6 md:grid-cols-2">
    <Link
      v-for="article in articles.data"
      :key="article.id"
      :href="`/help/${article.slug}`"
      class="rounded-2xl bg-white p-7"
      ><h2 class="text-xl">{{ article.title }}</h2>
      <p
        class="mt-4 line-clamp-3 whitespace-pre-wrap text-sm leading-6 text-stone-500"
      >
        {{ article.body }}
      </p>
      <span class="mt-6 block text-sm">{{ t('阅读全文 ↗') }}</span></Link
    >
  </div>
  <nav class="mt-8 flex gap-6">
    <Link v-if="articles.prev_page_url" :href="articles.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="articles.next_page_url" :href="articles.next_page_url">{{
      t('下一页')
    }}</Link>
  </nav>
</template>
