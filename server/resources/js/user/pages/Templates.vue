<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const { t } = useInterface();

const props = defineProps<{
  templates: {
    data: {
      id: string;
      title: string;
      description: string;
      category: string;
      kind: string;
      tags: string[];
      cover_url: string | null;
    }[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
  filters: { search?: string; category?: string; kind?: string; tag?: string };
  categories: string[];
}>();
const filters = reactive({
  search: props.filters.search ?? '',
  category: props.filters.category ?? '',
  kind: props.filters.kind ?? '',
  tag: props.filters.tag ?? '',
});

function search() {
  router.get('/templates', filters, { preserveState: true });
}
</script>
<template>
  <Head :title="t('模板灵感库')" />
  <p class="text-sm text-orange-700">THE INSPIRATION LIBRARY</p>
  <h1 class="mt-3 text-4xl">{{ t('从一个灵感开始') }}</h1>
  <form class="my-8 flex flex-wrap gap-4" @submit.prevent="search">
    <input
      v-model="filters.search"
      :aria-label="t('搜索模板')"
      :placeholder="t('搜索模板')"
      maxlength="100"
      class="rounded-lg border bg-white p-3"
    /><select
      v-model="filters.category"
      :aria-label="t('分类')"
      class="rounded-lg border bg-white p-3"
    >
      <option value="">{{ t('全部分类') }}</option>
      <option v-for="category in categories" :key="category">
        {{ category }}
      </option></select
    ><select
      v-model="filters.kind"
      :aria-label="t('创作类型')"
      class="rounded-lg border bg-white p-3"
    >
      <option value="">{{ t('全部类型') }}</option>
      <option value="text_to_image">{{ t('文字生图') }}</option>
      <option value="image_to_image">{{ t('图片编辑') }}</option>
      <option value="image_to_video">{{ t('照片动画') }}</option></select
    ><input
      v-model="filters.tag"
      :aria-label="t('标签')"
      :placeholder="t('按标签筛选')"
      maxlength="30"
      class="rounded-lg border bg-white p-3"
    /><button class="rounded-full bg-stone-900 px-6 text-white">
      {{ t('筛选') }}
    </button>
  </form>
  <p v-if="!templates.data.length" class="py-16 text-stone-500">
    {{ t('暂无符合条件的已发布模板。') }}
  </p>
  <section class="grid gap-6 md:grid-cols-3">
    <Link
      v-for="template in templates.data"
      :key="template.id"
      :href="`/templates/${template.id}`"
      class="rounded-2xl border border-stone-200 bg-white p-7"
      ><img
        v-if="template.cover_url"
        :src="template.cover_url"
        :alt="template.title"
        loading="lazy"
        class="mb-5 aspect-[4/3] w-full rounded-xl object-cover"
      />
      <p class="text-xs text-orange-700">{{ template.category }}</p>
      <h2 class="mt-6 text-2xl">{{ template.title }}</h2>
      <p class="mt-4 line-clamp-3 text-sm leading-6 text-stone-500">
        {{ template.description }}
      </p>
      <p v-if="template.tags.length" class="mt-4 text-xs text-stone-500">
        {{ template.tags.join(' · ') }}
      </p>
      <p class="mt-8 text-sm">{{ t('查看模板 ↗') }}</p></Link
    >
  </section>
  <nav class="mt-8 flex gap-6">
    <Link v-if="templates.prev_page_url" :href="templates.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="templates.next_page_url" :href="templates.next_page_url">{{
      t('下一页')
    }}</Link>
  </nav>
</template>
