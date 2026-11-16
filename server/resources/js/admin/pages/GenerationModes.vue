<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
  NCard,
  NAlert,
  NFormItem,
  NSwitch,
  NSelect,
  NInput,
  NButton,
} from 'naive-ui';

const { t } = useInterface();

interface Mode {
  kind: string;
  active: boolean;
  aspect_ratios: string[];
  pause_message: string | null;
  version: number;
}
defineProps<{ modes: Mode[]; pendingLimit: number }>();
const selected = ref<string | null>(null);
const form = useForm({
  active: true,
  aspect_ratios: [] as string[],
  pause_message: '',
  version: 1,
  reason: '',
});
const labels: Record<string, string> = {
  text_to_image: t('文字生图'),
  image_to_image: t('图片编辑'),
  image_to_video: t('照片动画'),
};
const options = ['1:1', '16:9', '9:16', '4:3', '3:4'].map((value) => ({
  label: value,
  value,
}));

function edit(mode: Mode) {
  selected.value = mode.kind;
  Object.assign(form, {
    active: mode.active,
    aspect_ratios: [...mode.aspect_ratios],
    pause_message: mode.pause_message ?? '',
    version: mode.version,
    reason: '',
  });
  form.clearErrors();
}

function save() {
  if (!selected.value) {
    return;
  }

  form.put(`/admin/generation-modes/${selected.value}`, {
    onSuccess: () => {
      selected.value = null;
    },
  });
}
</script>
<template>
  <Head :title="t('创作模式')" />
  <h1>{{ t('创作模式与接单控制') }}</h1>
  <NAlert type="info" style="margin-bottom: 24px"
    >{{
      t(
        '此处配置 Web 任务接单，不调用模型。参数仅记录创作意图；每用户待处理上限',
      )
    }}
    {{ pendingLimit
    }}{{
      t('，由环境变量 GENERATION_PENDING_LIMIT 维护。暂停不会取消已有任务。')
    }}</NAlert
  ><NCard
    v-if="selected"
    :title="t('编辑 {0}', { 0: labels[selected] })"
    style="margin-bottom: 24px"
    ><form @submit.prevent="save">
      <NFormItem :label="t('允许记录新任务')"
        ><NSwitch v-model:value="form.active" /></NFormItem
      ><NFormItem :label="t('可选画面比例')"
        ><NSelect
          v-model:value="form.aspect_ratios"
          multiple
          :options="options" /></NFormItem
      ><NFormItem :label="t('用户端暂停提示')"
        ><NInput
          v-model:value="form.pause_message"
          :maxlength="200" /></NFormItem
      ><NFormItem :label="t('变更原因')"
        ><NInput v-model:value="form.reason" :maxlength="1000" /></NFormItem
      ><NAlert v-for="error in form.errors" :key="error" type="error">{{
        error
      }}</NAlert
      ><NButton attr-type="submit" type="primary" :loading="form.processing">{{
        t('保存配置')
      }}</NButton
      ><NButton style="margin-left: 16px" @click="selected = null">{{
        t('取消')
      }}</NButton>
    </form></NCard
  ><NCard
    v-for="mode in modes"
    :key="mode.kind"
    :title="labels[mode.kind]"
    style="margin-bottom: 16px"
    ><p>
      {{ mode.active ? t('接单中') : t('已暂停') }} {{ t('· 版本') }}
      {{ mode.version }}
    </p>
    <p>
      {{ mode.aspect_ratios.join(' / ')
      }}{{ mode.kind === 'image_to_video' ? t(' · 期望时长 5 秒') : '' }}
    </p>
    <p v-if="!mode.active">{{ mode.pause_message }}</p>
    <NButton @click="edit(mode)">{{ t('编辑接单配置') }}</NButton></NCard
  >
</template>
