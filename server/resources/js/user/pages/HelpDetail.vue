<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { Head, Link } from '@inertiajs/vue3';

const { t, formatDate } = useInterface();

defineProps<{
  article: { title: string; body: string; published_at: string };
}>();
</script>
<template>
  <Head :title="article.title" /><Link
    href="/help"
    class="text-sm text-stone-500"
    >{{ t('← 返回帮助中心') }}</Link
  >
  <article class="mx-auto mt-8 max-w-3xl rounded-3xl bg-white p-8 md:p-12">
    <h1 class="text-3xl">{{ article.title }}</h1>
    <time class="mt-4 block text-xs text-stone-400"
      >{{ t('更新于') }} {{ formatDate(article.published_at, true) }}</time
    >
    <p class="mt-8 whitespace-pre-wrap break-words leading-8">
      {{ article.body }}
    </p>
  </article>
  <p class="mt-8 text-center text-sm text-stone-500">
    {{ t('还有问题？')
    }}<Link href="/support" class="underline">{{ t('联系支持') }}</Link>
  </p>
</template>
