<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
  NCard,
  NInput,
  NSelect,
  NButton,
  NFormItem,
  NAlert,
  NSpace,
} from 'naive-ui';

const { t } = useInterface();

interface Content {
  id: string;
  slug: string;
  kind: string;
  current_version: number;
  published_version: number | null;
  title: string;
  body: string;
}
defineProps<{
  contents: {
    data: Content[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
}>();
const editing = ref<string | null>(null);
const form = useForm({
  slug: '',
  kind: 'help',
  title: '',
  body: '',
  version: 1,
  reason: '',
});
const publication = useForm({ version: 1, reason: '' });

function edit(content: Content) {
  editing.value = content.id;
  Object.assign(form, {
    slug: content.slug,
    kind: content.kind,
    title: content.title,
    body: content.body,
    version: content.current_version,
    reason: '',
  });
  form.clearErrors();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function save() {
  form.transform((data) => {
    if (!editing.value) {
      return data;
    }

    const { slug, kind, ...revision } = data;
    return revision;
  });
  const options = {
    onSuccess: () => {
      editing.value = null;
      form.reset();
    },
  };
  if (editing.value) {
    form.put(`/admin/content/${editing.value}`, options);
  } else {
    form.post('/admin/content', options);
  }
}

function publish(content: Content, action: 'publish' | 'withdraw') {
  publication.version = content.current_version;
  publication.post(`/admin/content/${content.id}/${action}`, {
    preserveScroll: true,
  });
}
</script>
<template>
  <Head :title="t('运营内容')" />
  <h1>{{ t('公告与帮助内容') }}</h1>
  <NAlert type="info" style="margin-bottom: 24px">{{
    t(
      '内容以纯文本展示。保存草稿不影响已发布版本，发布后用户可见，撤回后立即隐藏。',
    )
  }}</NAlert
  ><NCard
    :title="editing ? t('编辑草稿') : t('新建内容')"
    style="margin-bottom: 24px"
    ><form @submit.prevent="save">
      <NFormItem :label="t('固定路径标识（小写字母、数字、连字符）')"
        ><NInput
          v-model:value="form.slug"
          :disabled="!!editing"
          :maxlength="100" /></NFormItem
      ><NFormItem :label="t('类型')"
        ><NSelect
          v-model:value="form.kind"
          :disabled="!!editing"
          :options="[
            { label: t('帮助文章'), value: 'help' },
            { label: t('首页公告'), value: 'announcement' },
          ]" /></NFormItem
      ><NFormItem :label="t('标题')"
        ><NInput v-model:value="form.title" :maxlength="160" /></NFormItem
      ><NFormItem :label="t('正文')"
        ><NInput
          v-model:value="form.body"
          type="textarea"
          :autosize="{ minRows: 5 }"
          :maxlength="20000" /></NFormItem
      ><NFormItem :label="t('修改原因')"
        ><NInput v-model:value="form.reason" :maxlength="1000" /></NFormItem
      ><NAlert v-for="error in form.errors" :key="error" type="error">{{
        error
      }}</NAlert
      ><NSpace
        ><NButton
          attr-type="submit"
          type="primary"
          :loading="form.processing"
          >{{ t('保存草稿') }}</NButton
        ><NButton
          v-if="editing"
          @click="
            editing = null;
            form.reset();
            form.clearErrors();
          "
          >{{ t('取消编辑') }}</NButton
        ></NSpace
      >
    </form>
    <details style="margin-top: 20px">
      <summary>{{ t('草稿预览') }}</summary>
      <h2>{{ form.title }}</h2>
      <p style="white-space: pre-wrap; overflow-wrap: anywhere">
        {{ form.body }}
      </p>
    </details></NCard
  ><NFormItem :label="t('发布 / 撤回原因')"
    ><NInput v-model:value="publication.reason" :maxlength="1000" /></NFormItem
  ><NAlert v-for="error in publication.errors" :key="error" type="error">{{
    error
  }}</NAlert>
  <p v-if="!contents.data.length">{{ t('暂无内容。') }}</p>
  <NCard
    v-for="content in contents.data"
    :key="content.id"
    :title="content.title"
    style="margin-bottom: 16px"
    ><p>
      {{ content.kind === 'help' ? t('帮助文章') : t('首页公告') }} ·
      {{ content.slug }} {{ t('· 当前草稿 v') }}{{ content.current_version }} ·
      {{
        content.published_version
          ? t('线上 v{0}', { 0: content.published_version })
          : t('未发布')
      }}
    </p>
    <NSpace
      ><NButton @click="edit(content)">{{ t('编辑草稿') }}</NButton
      ><NButton
        v-if="content.current_version !== content.published_version"
        :disabled="publication.processing"
        @click="publish(content, 'publish')"
        >{{ t('发布当前版本') }}</NButton
      ><NButton
        v-if="content.published_version"
        :disabled="publication.processing"
        @click="publish(content, 'withdraw')"
        >{{ t('撤回') }}</NButton
      ><Link
        v-if="content.published_version && content.kind === 'help'"
        :href="`/help/${content.slug}`"
        >{{ t('查看线上内容') }}</Link
      ></NSpace
    >
    <details style="margin-top: 16px">
      <summary>{{ t('查看当前草稿') }}</summary>
      <p style="white-space: pre-wrap; overflow-wrap: anywhere">
        {{ content.body }}
      </p>
    </details></NCard
  ><NSpace
    ><Link v-if="contents.prev_page_url" :href="contents.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="contents.next_page_url" :href="contents.next_page_url">{{
      t('下一页')
    }}</Link></NSpace
  >
</template>
