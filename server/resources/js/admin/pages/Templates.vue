<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { ref } from 'vue';
import { uploadImage } from '../../shared/uploadImage';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
  NCard,
  NInput,
  NButton,
  NSelect,
  NFormItem,
  NAlert,
  NSpace,
  NTag,
  NInputNumber,
} from 'naive-ui';

const { t } = useInterface();

interface Template {
  id: string;
  title: string;
  description: string;
  prompt: string;
  kind: string;
  category: string;
  source_license: string;
  status: string;
  version: number;
  cover_media_id: string | null;
  tags: string[];
  sort_order: number;
}
defineProps<{
  templates: {
    data: Template[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
}>();
const editing = ref<string | null>(null);
const form = useForm({
  title: '',
  description: '',
  kind: 'text_to_image',
  prompt: '',
  category: '',
  source_license: '',
  reason: '',
  version: 1,
  cover_media_id: null as string | null,
  tags: [] as string[],
  sort_order: 0,
});
const tagsText = ref('');
const uploading = ref(false);
const uploadError = ref('');

const statusForm = useForm({ status: '', reason: '', version: 1 });
const options = [
  { label: t('文字生图'), value: 'text_to_image' },
  { label: t('图片编辑'), value: 'image_to_image' },
  { label: t('照片动画 · 5 秒'), value: 'image_to_video' },
];
const labels: Record<string, string> = {
  draft: t('草稿'),
  pending_review: t('待审核'),
  published: t('已发布'),
  archived: t('已下架'),
};

function edit(template: Template) {
  editing.value = template.id;
  Object.assign(form, {
    title: template.title,
    description: template.description,
    kind: template.kind,
    prompt: template.prompt,
    category: template.category,
    source_license: template.source_license,
    reason: '',
    version: template.version,
    cover_media_id: template.cover_media_id,
    tags: template.tags,
    sort_order: template.sort_order,
  });
  tagsText.value = template.tags.join(', ');
  form.clearErrors();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function uploadCover(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (!file) {
    return;
  }

  uploading.value = true;
  uploadError.value = '';
  try {
    form.cover_media_id = await uploadImage(file, '/admin/template-media');
  } catch (error) {
    uploadError.value =
      error instanceof Error ? error.message : t('上传失败，请重试。');
  } finally {
    uploading.value = false;
  }
}

function save() {
  form.tags = tagsText.value
    .split(/[,，]/)
    .map((tag) => tag.trim())
    .filter(Boolean);
  const options = {
    onSuccess: () => {
      form.reset();
      tagsText.value = '';
      editing.value = null;
    },
  };
  if (editing.value) {
    form.put(`/admin/templates/${editing.value}`, options);
  } else {
    form.post('/admin/templates', options);
  }
}

function changeStatus(template: Template, status: string) {
  statusForm.status = status;
  statusForm.version = template.version;
  statusForm.post(`/admin/templates/${template.id}/status`, {
    preserveScroll: true,
  });
}
</script>
<template>
  <Head :title="t('模板管理')" />
  <h1>{{ t('模板管理') }}</h1>
  <NAlert type="info" style="margin-bottom: 24px">{{
    t(
      '模板编辑会生成新版本并回到草稿；历史任务保留原版本。先提交审核，再发布。发布前必须确认素材来源与授权。',
    )
  }}</NAlert
  ><NCard
    :title="editing ? t('编辑模板 · 保存为新版本') : t('新增模板')"
    style="margin-bottom: 24px"
    ><form @submit.prevent="save">
      <NFormItem :label="t('标题')"
        ><NInput v-model:value="form.title" :maxlength="120" /></NFormItem
      ><NFormItem :label="t('说明')"
        ><NInput
          v-model:value="form.description"
          type="textarea"
          :maxlength="2000" /></NFormItem
      ><NFormItem :label="t('创作类型')"
        ><NSelect v-model:value="form.kind" :options="options" /></NFormItem
      ><NFormItem :label="t('提示词')"
        ><NInput
          v-model:value="form.prompt"
          type="textarea"
          :maxlength="4000" /></NFormItem
      ><NFormItem :label="t('分类')"
        ><NInput v-model:value="form.category" :maxlength="80" /></NFormItem
      ><NFormItem :label="t('封面图片（公开链接，最大 10MB）')"
        ><div>
          <input
            type="file"
            accept="image/jpeg,image/png,image/webp"
            :disabled="uploading"
            @change="uploadCover"
          />
          <p v-if="uploading">{{ t('正在上传…') }}</p>
          <p v-if="uploadError" role="alert">{{ uploadError }}</p>
          <img
            v-if="form.cover_media_id"
            :src="`/admin/template-media/${form.cover_media_id}`"
            :alt="t('草稿封面')"
            style="
              display: block;
              max-height: 180px;
              max-width: 100%;
              margin-top: 12px;
            "
          /><NButton
            v-if="form.cover_media_id"
            @click="form.cover_media_id = null"
            >{{ t('移除封面') }}</NButton
          >
        </div></NFormItem
      ><NFormItem :label="t('标签（逗号分隔，最多 12 个）')"
        ><NInput v-model:value="tagsText" /></NFormItem
      ><NFormItem :label="t('排序值（越小越靠前）')"
        ><NInputNumber
          v-model:value="form.sort_order"
          :min="0"
          :max="1000000"
          :precision="0" /></NFormItem
      ><NFormItem :label="t('来源与授权记录')"
        ><NInput
          v-model:value="form.source_license"
          type="textarea"
          :maxlength="2000" /></NFormItem
      ><NFormItem :label="t('修改原因')"
        ><NInput v-model:value="form.reason" :maxlength="1000" /></NFormItem
      ><NAlert v-for="error in form.errors" :key="error" type="error">{{
        error
      }}</NAlert
      ><NSpace
        ><NButton
          type="primary"
          attr-type="submit"
          :loading="form.processing"
          :disabled="uploading"
          >{{ t('保存草稿') }}</NButton
        ><NButton
          v-if="editing"
          @click="
            editing = null;
            form.reset();
            tagsText = '';
            form.clearErrors();
          "
          >{{ t('取消编辑') }}</NButton
        ></NSpace
      >
    </form></NCard
  ><NFormItem :label="t('审核 / 发布 / 下架原因（每次操作必填）')"
    ><NInput v-model:value="statusForm.reason" :maxlength="1000" /></NFormItem
  ><NAlert v-for="error in statusForm.errors" :key="error" type="error">{{
    error
  }}</NAlert
  ><NCard
    v-for="template in templates.data"
    :key="template.id"
    :title="template.title"
    style="margin-bottom: 16px"
    ><NSpace
      ><NTag>{{ labels[template.status] }}</NTag
      ><span
        >{{ t('版本') }} {{ template.version }} · {{ template.category }}</span
      ></NSpace
    >
    <img
      v-if="template.cover_media_id"
      :src="`/admin/template-media/${template.cover_media_id}`"
      :alt="t('模板封面')"
      style="max-height: 120px; max-width: 100%; margin-top: 16px"
    />
    <p>{{ template.description }}</p>
    <p>
      {{ template.tags.join(' · ') }} {{ t('· 排序') }}
      {{ template.sort_order }}
    </p>
    <NSpace
      ><NButton @click="edit(template)">{{ t('编辑新版本') }}</NButton
      ><NButton
        v-if="['draft', 'archived'].includes(template.status)"
        :disabled="statusForm.processing"
        @click="changeStatus(template, 'pending_review')"
        >{{ t('提交审核') }}</NButton
      ><NButton
        v-if="template.status === 'pending_review'"
        :disabled="statusForm.processing"
        @click="changeStatus(template, 'published')"
        >{{ t('审核并发布') }}</NButton
      ><NButton
        v-if="['published', 'pending_review'].includes(template.status)"
        :disabled="statusForm.processing"
        @click="changeStatus(template, 'archived')"
        >{{ t('下架') }}</NButton
      ></NSpace
    ></NCard
  ><NSpace
    ><Link v-if="templates.prev_page_url" :href="templates.prev_page_url">{{
      t('上一页')
    }}</Link
    ><Link v-if="templates.next_page_url" :href="templates.next_page_url">{{
      t('下一页')
    }}</Link></NSpace
  >
</template>
