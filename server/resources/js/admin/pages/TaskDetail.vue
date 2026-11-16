<script setup lang="ts">
import { useInterface } from '../../shared/interface';

import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { NCard, NAlert, NInput, NSelect, NFormItem, NButton } from 'naive-ui';

const { t } = useInterface();

const props = defineProps<{
  task: {
    id: string;
    user_id: number;
    kind: string;
    status: string;
    moderation_status: string;
    prompt?: string;
    asset_id?: string | null;
    template_id?: string | null;
    template_version?: number | null;
    parameters?: { aspect_ratio: string; duration_seconds: number | null };
    mode_version?: number;
  };
  review: { decision: string; reason: string; created_at: string } | null;
  events: { id: number; type: string; created_at: string }[];
}>();
const page = usePage<{ admin: { permissions: string[] } }>();
const form = useForm({
  decision: 'approved',
  reason: '',
  idempotency_key: crypto.randomUUID(),
});

function submitReview() {
  form.post(`/admin/tasks/${props.task.id}/review`);
}
</script>
<template>
  <Head :title="t('任务详情')" /><Link href="/admin/tasks">{{
    t('← 返回任务列表')
  }}</Link>
  <h1>{{ t('任务详情') }}</h1>
  <NCard :title="t('任务记录')"
    ><p style="overflow-wrap: anywhere">{{ task.id }}</p>
    <p>{{ t('用户') }} {{ task.user_id }} · {{ task.kind }}</p>
    <p>
      {{ t('执行状态：') }}{{ task.status }} {{ t('· 审核状态：')
      }}{{ task.moderation_status }}
    </p>
    <p v-if="task.template_id">
      {{ t('模板') }} {{ task.template_id }} {{ t('· 版本') }}
      {{ task.template_version }}
    </p>
    <template v-if="task.prompt !== undefined"
      ><h2>{{ t('输入描述') }}</h2>
      <p style="white-space: pre-wrap; overflow-wrap: anywhere">
        {{ task.prompt }}
      </p>
      <img
        v-if="task.asset_id"
        :src="`/admin/tasks/${task.id}/input`"
        :alt="t('用户参考图片')"
        style="max-width: 100%; max-height: 400px" /></template
    ><NAlert v-else type="info">{{
      t('当前角色只可查看任务元数据。')
    }}</NAlert></NCard
  ><NCard v-if="review" :title="t('审核结论')" style="margin-top: 24px"
    ><p>{{ review.decision }} · {{ review.reason }}</p>
    <time>{{ review.created_at }}</time></NCard
  ><NCard
    v-if="
      !review &&
      task.status === 'pending' &&
      page.props.admin.permissions.includes('tasks.review')
    "
    :title="t('输入审核')"
    style="margin-top: 24px"
    ><form @submit.prevent="submitReview">
      <NFormItem :label="t('结论')"
        ><NSelect
          v-model:value="form.decision"
          :options="[
            { label: t('通过'), value: 'approved' },
            { label: t('拒绝'), value: 'rejected' },
          ]" /></NFormItem
      ><NFormItem :label="t('原因')"
        ><NInput
          v-model:value="form.reason"
          type="textarea"
          :maxlength="1000" /></NFormItem
      ><NAlert v-for="error in form.errors" :key="error" type="error">{{
        error
      }}</NAlert
      ><NButton attr-type="submit" type="primary" :loading="form.processing">{{
        t('保存审核结论')
      }}</NButton>
    </form></NCard
  ><NCard :title="t('最近任务事件')" style="margin-top: 24px"
    ><p v-if="!events.length">{{ t('暂无事件。') }}</p>
    <p v-for="event in events" :key="event.id">
      {{ event.created_at }} · {{ event.type }}
    </p></NCard
  >
</template>
