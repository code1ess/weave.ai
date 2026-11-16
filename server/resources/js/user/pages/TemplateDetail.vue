<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { Head, Link } from '@inertiajs/vue3';

const { t } = useInterface();

defineProps<{
  template: {
    id: string;
    title: string;
    description: string;
    prompt: string;
    kind: string;
    category: string;
    version: number;
    tags: string[];
    cover_url: string | null;
  };
}>();
</script>
<template>
  <Head :title="template.title" /><Link
    href="/templates"
    class="text-sm text-stone-500"
    >{{ t('← 返回模板库') }}</Link
  >
  <section class="mt-8 max-w-3xl rounded-3xl bg-white p-10">
    <p class="text-sm text-orange-700">
      {{ template.category }} {{ t('· 版本') }} {{ template.version }}
    </p>
    <img
      v-if="template.cover_url"
      :src="template.cover_url"
      :alt="template.title"
      class="mt-6 max-h-96 w-full rounded-xl object-cover"
    />
    <p v-if="template.tags.length" class="mt-5 text-sm text-stone-500">
      {{ template.tags.join(' · ') }}
    </p>
    <h1 class="mt-5 text-4xl">{{ template.title }}</h1>
    <p class="mt-6 whitespace-pre-wrap leading-8 text-stone-600">
      {{ template.description }}
    </p>
    <h2 class="mt-8 font-semibold">{{ t('创意提示词') }}</h2>
    <p class="mt-3 whitespace-pre-wrap rounded-xl bg-stone-50 p-5">
      {{ template.prompt }}
    </p>
    <p class="my-6 text-sm text-stone-500">
      {{
        template.kind === 'text_to_image'
          ? t('输入文字描述即可提交。')
          : t('需要上传一张参考图片。')
      }}{{ t('当前仅记录任务，不生成结果，不收取点数。') }}
    </p>
    <Link
      :href="`/studio?template=${template.id}`"
      class="inline-block rounded-full bg-stone-900 px-8 py-3 text-white"
      >{{ t('使用这个模板 ↗') }}</Link
    >
  </section>
</template>
