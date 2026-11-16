<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { ref, computed, watch } from 'vue';
import { synchronizeStudioSelection } from '../../shared/studioSelection';
import { uploadImage } from '../../shared/uploadImage';
import { Head, Link, useForm } from '@inertiajs/vue3';

const { t, formatDate } = useInterface();

const props = defineProps<{
  modes: {
    kind: string;
    active: boolean;
    aspect_ratios: string[];
    pause_message: string | null;
    version: number;
  }[];
  pendingLimit: number;
  selectedKind?: string | null;
  selectedAsset: string | null;
  selectedTemplate: {
    template_id: string;
    version: number;
    title: string;
    kind: string;
    prompt: string;
  } | null;
  tasks: {
    data: {
      id: string;
      prompt: string;
      status: string;
      asset_id: string | null;
      kind: string;
      created_at: string;
    }[];
    prev_page_url: string | null;
    next_page_url: string | null;
  };
}>();
const cancellation = useForm({ task: '' });
const statusLabels: Record<string, string> = {
  pending: t('等待处理'),
  cancelled: t('已取消'),
  processing: t('处理中'),
  succeeded: t('已完成'),
  failed: t('失败'),
  rejected: t('审核拒绝'),
};

const form = useForm({
  kind:
    props.selectedTemplate?.kind ??
    props.selectedKind ??
    (props.selectedAsset ? 'image_to_image' : 'text_to_image'),
  prompt: props.selectedTemplate?.prompt ?? '',
  template_id: props.selectedTemplate?.template_id ?? null,
  template_version: props.selectedTemplate?.version ?? null,
  asset_id: props.selectedAsset,
  idempotency_key: crypto.randomUUID(),
  mode_version: null as number | null,
  parameters: {
    aspect_ratio: '1:1',
    duration_seconds: (props.selectedTemplate?.kind === 'image_to_video'
      ? 5
      : null) as number | null,
  },
});

synchronizeStudioSelection(
  form,
  () => props.selectedTemplate,
  () => props.selectedAsset,
  () => props.selectedKind ?? null,
);

const selectedMode = computed(() =>
  props.modes.find((mode) => mode.kind === form.kind),
);

watch(
  selectedMode,
  (mode) => {
    if (mode && !mode.aspect_ratios.includes(form.parameters.aspect_ratio)) {
      form.parameters.aspect_ratio = mode.aspect_ratios[0];
    }
    form.parameters.duration_seconds =
      form.kind === 'image_to_video' ? 5 : null;
  },
  { immediate: true },
);

const uploading = ref(false);
const uploadError = ref('');

async function upload(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  form.asset_id = null;
  uploadError.value = '';
  if (!file) {
    return;
  }

  uploading.value = true;
  try {
    form.asset_id = await uploadImage(file, '/assets');
  } catch (error) {
    uploadError.value =
      error instanceof Error ? error.message : t('上传失败，请重试。');
  } finally {
    uploading.value = false;
  }
}

function submit() {
  form.mode_version = selectedMode.value?.version ?? null;
  form.post('/tasks', {
    onSuccess: () => {
      form.reset('prompt');
      form.idempotency_key = crypto.randomUUID();
    },
  });
}
</script>
<template>
  <Head :title="t('创作工作台')" />
  <p class="text-sm text-orange-700">CREATIVE STUDIO</p>
  <h1 class="mt-3 mb-8 text-4xl font-semibold">
    {{ t('今天，想创造什么？') }}
  </h1>
  <div class="grid gap-8 lg:grid-cols-2">
    <section class="rounded-3xl bg-white p-8">
      <h2 class="text-xl">{{ t('创作任务') }}</h2>
      <p v-if="props.selectedTemplate" class="mt-4 text-sm text-orange-700">
        {{ t('套用模板：') }}{{ props.selectedTemplate.title }}
        {{ t('· 版本') }} {{ props.selectedTemplate.version }}
        <Link href="/studio" class="underline">{{ t('取消套用') }}</Link>
      </p>
      <label class="mt-5 block text-sm"
        >{{ t('创作模式') }}
        <select
          v-model="form.kind"
          class="mt-2 w-full rounded-lg border p-3"
          :disabled="form.processing || uploading || !!form.template_id"
        >
          <option value="text_to_image">{{ t('文字生图') }}</option>
          <option value="image_to_image">{{ t('图片编辑') }}</option>
          <option value="image_to_video">{{ t('照片动画 · 5 秒') }}</option>
        </select>
      </label>
      <div v-if="form.kind !== 'text_to_image'" class="mt-5">
        <p class="mb-3 text-xs text-stone-500">
          {{
            t('文件通过随机 UUID 公开链接访问，请勿上传不适合公开分享的图片。')
          }}
        </p>
        <label class="block text-sm"
          >{{ t('参考图片（JPG / PNG / WebP，最大 10MB）')
          }}<input
            type="file"
            accept="image/jpeg,image/png,image/webp"
            :disabled="form.processing || uploading"
            class="mt-2 block w-full"
            @change="upload"
        /></label>
        <p v-if="uploading" role="status">{{ t('正在上传…') }}</p>
        <p v-if="uploadError" role="alert" class="text-red-700">
          {{ uploadError }}
        </p>
        <img
          v-if="form.asset_id"
          :src="`/assets/${form.asset_id}`"
          :alt="t('已上传的参考图')"
          class="mt-4 max-h-48 rounded-xl"
        />
      </div>
      <p class="mt-3 mb-6 text-sm leading-6 text-stone-500">
        {{
          t(
            '描述主体、场景、光线与风格。当前仅保存任务，等待处理，不收取点数。',
          )
        }}
      </p>
      <p
        v-if="selectedMode && !selectedMode.active"
        role="status"
        class="mb-5 rounded-xl bg-orange-50 p-4 text-sm text-orange-800"
      >
        {{ selectedMode.pause_message || t('此模式暂时停止接单。') }}
      </p>
      <p class="mb-5 text-xs text-stone-500">
        {{ t('每个账号最多保留') }} {{ pendingLimit }}
        {{ t('个待处理任务。参数为创作意图，接入模型后确认实际支持范围。') }}
      </p>
      <label class="mb-5 block text-sm"
        >{{ t('画面比例')
        }}<select
          v-model="form.parameters.aspect_ratio"
          :disabled="form.processing"
          class="mt-2 w-full rounded-lg border p-3"
        >
          <option
            v-for="ratio in selectedMode?.aspect_ratios ?? []"
            :key="ratio"
            :value="ratio"
          >
            {{ ratio }}
          </option>
        </select></label
      >
      <p
        v-if="form.kind === 'image_to_video'"
        class="mb-5 text-sm text-stone-500"
      >
        {{ t('期望视频时长：5 秒') }}
      </p>
      <form @submit.prevent="submit">
        <label for="prompt" class="text-sm">{{ t('创意描述') }}</label
        ><textarea
          id="prompt"
          v-model="form.prompt"
          :disabled="form.processing"
          required
          maxlength="4000"
          rows="8"
          class="mt-3 w-full rounded-xl border border-stone-300 p-4"
          :placeholder="t('例如：晨光穿过山间薄雾，胶片摄影风格……')"
        />
        <p
          v-for="error in form.errors"
          :key="error"
          role="alert"
          class="text-sm text-red-700"
        >
          {{ error }}
        </p>
        <button
          :disabled="
            form.processing ||
            !selectedMode?.active ||
            uploading ||
            (form.kind !== 'text_to_image' && !form.asset_id)
          "
          class="mt-5 rounded-full bg-orange-600 px-8 py-3 text-white disabled:opacity-50"
        >
          {{ form.processing ? t('提交中…') : t('记录创作任务 ↗') }}
        </button>
      </form>
    </section>
    <section>
      <h2 class="mb-5 text-xl">{{ t('最近的创意') }}</h2>
      <p v-if="cancellation.errors.task" role="alert" class="mb-4 text-red-700">
        {{ cancellation.errors.task }}
      </p>
      <p
        v-if="!tasks.data.length"
        class="rounded-2xl border border-dashed border-stone-300 p-10 text-stone-500"
      >
        {{ t('还没有任务，从左侧记录第一个创意。') }}
      </p>
      <article
        v-for="task in tasks.data"
        :key="task.id"
        class="mb-4 rounded-2xl border border-stone-200 bg-white p-5"
      >
        <span class="text-xs text-orange-700">{{
          statusLabels[task.status] ?? task.status
        }}</span>
        <img
          v-if="task.asset_id"
          :src="`/assets/${task.asset_id}`"
          :alt="t('任务参考图')"
          class="mt-3 max-h-32 rounded-lg"
        />
        <Link
          :href="`/works/${task.id}`"
          class="mt-4 block text-xs underline"
          >{{ t('查看详情') }}</Link
        >
        <p class="mt-3 whitespace-pre-wrap break-words">{{ task.prompt }}</p>
        <div class="mt-4 flex gap-5 text-sm">
          <button
            v-if="task.status === 'pending'"
            :disabled="cancellation.processing"
            @click="
              cancellation.post(`/tasks/${task.id}/cancel`, {
                preserveScroll: true,
              })
            "
          >
            {{ t('取消任务') }}</button
          ><Link :href="`/support?task=${task.id}`">{{ t('反馈问题') }}</Link>
        </div>
        <time class="mt-4 block text-xs text-stone-400">{{
          formatDate(task.created_at)
        }}</time>
      </article>
      <div class="flex gap-6 text-sm">
        <Link v-if="tasks.prev_page_url" :href="tasks.prev_page_url">{{
          t('上一页')
        }}</Link
        ><Link v-if="tasks.next_page_url" :href="tasks.next_page_url">{{
          t('下一页')
        }}</Link>
      </div>
    </section>
  </div>
</template>
